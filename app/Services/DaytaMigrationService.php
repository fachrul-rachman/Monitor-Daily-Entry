<?php

namespace App\Services;

use App\Services\MetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DaytaMigrationService
{
    /**
     * @param array{
     *   attachments_root: string,
     *   db_a: array{host:string,port:int,database:string,username:string,password:string},
     *   report_path: string,
     *   preserve_item_ids: bool,
     *   default_relation_reason: string,
     *   map_real_only_plan_title_prefix: string,
 * } $opts
 */
    public function migrate(array $opts): array
    {
        $startedAt = Carbon::now();

        $report = new MigrationReport($opts['report_path']);
        $report->h1('Dayta Migration (db A -> db B)');
        $report->kv([
            'Started At' => $startedAt->toDateTimeString(),
            'Attachments Root' => $opts['attachments_root'],
            'db A (host:port/db)' => $opts['db_a']['host'].':'.$opts['db_a']['port'].'/'.$opts['db_a']['database'],
            'Preserve daily_entry_items.id' => $opts['preserve_item_ids'] ? 'yes' : 'no',
            'Dry Run' => ! empty($opts['dry_run']) ? 'yes' : 'no',
        ]);

        $stats = [
            'source' => [],
            'inserted' => [],
            'updated' => [],
            'attachments' => ['copied' => 0, 'skipped' => 0, 'missing' => 0, 'renamed_due_to_collision' => 0],
            'pairing' => ['merged_pairs' => 0, 'plan_only' => 0, 'real_only' => 0, 'both_in_one_row' => 0],
            'warnings' => [],
        ];

        try {
            $this->ensureAttachmentRootLooksValid($opts['attachments_root'], $report);
            $this->configureDbAConnection($opts['db_a']);

            $a = DB::connection('daytaA');
            $b = DB::connection('pgsql');

            $report->h2('Preflight Counts');
            $sourceTables = [
                'users',
                'divisions',
                'division_hod_assignments',
                'big_rocks',
                'daily_entries',
                'daily_entry_items',
                'daily_entry_item_attachments',
                'discord_notifications',
                'admin_overrides',
                'report_settings',
            ];

            foreach ($sourceTables as $t) {
                $stats['source'][$t] = $this->safeCount($a, $t);
            }

            $report->tableAssoc($stats['source'], 'db A row counts');

            $report->h2('Migration Steps');

            if (! empty($opts['dry_run'])) {
                $report->stepOk('Dry run: stop sebelum insert ke db B.');
                return [
                    'report_path' => $opts['report_path'],
                    'stats' => $stats,
                ];
            }

            $b->transaction(function () use ($a, $b, &$stats, $opts, $report): void {
                $this->migrateDivisions($a, $b, $stats, $report);
                $this->migrateUsers($a, $b, $stats, $report);
                $this->migrateHodAssignments($a, $b, $stats, $report);
                $this->migrateReportSettings($a, $b, $stats, $report);

                $bigRockMapByUser = $this->migrateBigRocksSharedToManagersAndHods($a, $b, $stats, $report);

                $this->migrateDailyEntries($a, $b, $stats, $report);

                [$itemIdMap, $itemSummary] = $this->migrateDailyEntryItemsWithPlanRealizationPairing(
                    $a,
                    $b,
                    $stats,
                    $report,
                    $opts,
                    $bigRockMapByUser,
                );

                $stats['pairing'] = array_merge($stats['pairing'], $itemSummary);

                $this->migrateAttachments($a, $b, $stats, $report, $opts['attachments_root'], $itemIdMap);
                $this->backfillLegacyAttachmentPath($b, $stats, $report);

                $this->migrateDiscordNotificationsToNotificationLogs($a, $b, $stats, $report);
                $this->migrateAdminOverridesToOverrideLogs($a, $b, $stats, $report);
            });

            $report->h2('Post-Commit Steps');
            try {
                $this->resetSequences($b, $stats, $report);
            } catch (\Throwable $e) {
                $stats['warnings'][] = 'Reset sequences gagal: '.$e->getMessage();
                $report->stepFail('Reset Sequences', $e);
            }

            try {
                $this->recomputeMetrics($a, $b, $stats, $report);
            } catch (\Throwable $e) {
                $stats['warnings'][] = 'Recompute metrics gagal: '.$e->getMessage();
                $report->stepFail('Recompute Findings + Health Scores', $e);
            }

            return [
                'report_path' => $opts['report_path'],
                'stats' => $stats,
            ];
        } finally {
            $finishedAt = Carbon::now();

            $report->h2('Summary');
            $report->kv([
                'Finished At' => $finishedAt->toDateTimeString(),
                'Duration' => $startedAt->diffAsCarbonInterval($finishedAt)->forHumans(),
                'Attachments Copied' => (string) $stats['attachments']['copied'],
                'Attachments Missing' => (string) $stats['attachments']['missing'],
                'Pairs Merged' => (string) $stats['pairing']['merged_pairs'],
                'Plan Only' => (string) $stats['pairing']['plan_only'],
                'Real Only' => (string) $stats['pairing']['real_only'],
            ]);

            $report->h2('Inserted Counts (db B)');
            $report->tableAssoc($stats['inserted'], 'rows inserted');

            $report->h2('Attachment Copy Details');
            $report->tableAssoc($stats['attachments'], 'attachment copy');

            if (! empty($stats['warnings'])) {
                $report->h2('Warnings');
                foreach ($stats['warnings'] as $w) {
                    $report->bullet($w);
                }
                $report->blank();
            }

            $report->write();
        }
    }

    private function ensureAttachmentRootLooksValid(string $root, MigrationReport $report): void
    {
        $dailyAttachments = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'daily_attachments';
        if (! is_dir($dailyAttachments)) {
            throw new \RuntimeException("Folder tidak ditemukan: {$dailyAttachments}");
        }

        $report->stepOk('Attachment root ditemukan: '.$dailyAttachments);
    }

    /**
     * @param array{host:string,port:int,database:string,username:string,password:string} $dbA
     */
    private function configureDbAConnection(array $dbA): void
    {
        $base = config('database.connections.pgsql');
        if (! is_array($base)) {
            throw new \RuntimeException('config database.connections.pgsql tidak valid.');
        }

        $base['host'] = $dbA['host'];
        $base['port'] = $dbA['port'];
        $base['database'] = $dbA['database'];
        $base['username'] = $dbA['username'];
        $base['password'] = $dbA['password'];

        config(['database.connections.daytaA' => $base]);
    }

    private function safeCount($conn, string $table): int
    {
        try {
            return (int) $conn->table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function migrateDivisions($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Divisions';
        try {
            $rows = $a->table('divisions')->orderBy('id')->get();

            $insert = [];
            foreach ($rows as $r) {
                $insert[] = [
                    'id' => (int) $r->id,
                    'name' => (string) $r->name,
                    'status' => ((bool) $r->is_active) ? 'active' : 'inactive',
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('divisions')->insert($insert);
            }

            $stats['inserted']['divisions'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateUsers($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Users';
        try {
            $validDivisionIds = $b->table('divisions')->pluck('id')->map(fn ($v) => (int) $v)->all();
            $validDivisionSet = array_fill_keys($validDivisionIds, true);

            $rows = $a->table('users')->orderBy('id')->get();

            $insert = [];
            foreach ($rows as $r) {
                $divisionId = $r->division_id ? (int) $r->division_id : null;
                if ($divisionId !== null && ! isset($validDivisionSet[$divisionId])) {
                    $stats['warnings'][] = "User id={$r->id} punya division_id={$divisionId} yang tidak ada di divisions, diset jadi kosong.";
                    $divisionId = null;
                }

                $insert[] = [
                    'id' => (int) $r->id,
                    'name' => (string) $r->name,
                    'email' => (string) $r->email,
                    'email_verified_at' => $r->email_verified_at,
                    'password' => (string) $r->password,
                    'remember_token' => $r->remember_token,
                    'two_factor_secret' => $r->two_factor_secret,
                    'two_factor_recovery_codes' => $r->two_factor_recovery_codes,
                    'two_factor_confirmed_at' => $r->two_factor_confirmed_at,
                    'role' => (string) ($r->role ?: 'manager'),
                    'division_id' => $divisionId,
                    'status' => ((bool) $r->is_active) ? 'active' : 'inactive',
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('users')->insert($insert);
            }

            $stats['inserted']['users'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateHodAssignments($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'HOD Assignments';
        try {
            $rows = $a->table('division_hod_assignments')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $chosenByDivision = [];
            foreach ($rows as $r) {
                $divisionId = (int) $r->division_id;
                if (! isset($chosenByDivision[$divisionId])) {
                    $chosenByDivision[$divisionId] = $r;
                }
            }

            $insert = [];
            foreach ($chosenByDivision as $r) {
                $insert[] = [
                    'id' => (int) $r->id,
                    'hod_id' => (int) $r->hod_user_id,
                    'division_id' => (int) $r->division_id,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('hod_assignments')->insert($insert);
            }

            $stats['inserted']['hod_assignments'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateReportSettings($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Report Settings';
        try {
            $rows = $a->table('report_settings')->orderBy('id')->get();

            $insert = [];
            foreach ($rows as $r) {
                $effectiveFrom = $r->created_at ? Carbon::parse($r->created_at)->toDateString() : Carbon::today()->toDateString();
                $insert[] = [
                    'id' => (int) $r->id,
                    'plan_open_time' => $this->normalizeTime((string) ($r->plan_open_rule ?: '07:00')),
                    'plan_close_time' => $this->normalizeTime((string) ($r->plan_close_rule ?: '10:00')),
                    'realization_open_time' => $this->normalizeTime((string) ($r->realization_open_rule ?: '15:00')),
                    'realization_close_time' => $this->normalizeTime((string) ($r->realization_close_rule ?: '23:00')),
                    'effective_from' => $effectiveFrom,
                    'is_active' => (bool) $r->is_active,
                    'discord_enabled' => false,
                    'discord_summary_time' => '20:00',
                    'discord_webhook_url' => null,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('report_settings')->insert($insert);
            }

            $stats['inserted']['report_settings'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function normalizeTime(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '00:00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $v)) {
            return substr($v, 0, 5);
        }
        if (preg_match('/^\d{2}:\d{2}$/', $v)) {
            return $v;
        }

        try {
            return Carbon::parse($v)->format('H:i');
        } catch (\Throwable) {
            return '00:00';
        }
    }

    /**
     * @return array<int, array<int, int>> map[user_id][old_big_rock_id]=new_big_rock_id
     */
    private function migrateBigRocksSharedToManagersAndHods($a, $b, array &$stats, MigrationReport $report): array
    {
        $step = 'Big Rocks (shared to managers+hods)';
        try {
            $users = $a->table('users')
                ->whereIn('role', ['manager', 'hod'])
                ->where('is_active', true)
                ->get(['id', 'division_id', 'role']);

            $usersByDivision = [];
            foreach ($users as $u) {
                if (! $u->division_id) {
                    continue;
                }
                $usersByDivision[(int) $u->division_id][] = (int) $u->id;
            }

            $bigRocks = $a->table('big_rocks')->orderBy('id')->get();
            $map = [];
            $insertedCount = 0;

            foreach ($bigRocks as $br) {
                $divisionId = (int) $br->division_id;
                $targetUserIds = $usersByDivision[$divisionId] ?? [];
                if (empty($targetUserIds)) {
                    $stats['warnings'][] = "Big Rock id={$br->id} division_id={$divisionId} tidak punya manager/hod aktif, jadi tidak dibuat di db B.";
                    continue;
                }

                foreach ($targetUserIds as $userId) {
                    $newId = (int) $b->table('big_rocks')->insertGetId([
                        'user_id' => $userId,
                        'title' => (string) $br->title,
                        'description' => $br->description,
                        'start_date' => $br->period_start,
                        'end_date' => $br->period_end,
                        'status' => (string) ($br->status ?: 'active'),
                        'created_at' => $br->created_at,
                        'updated_at' => $br->updated_at,
                    ]);
                    $map[$userId][(int) $br->id] = $newId;
                    $insertedCount++;
                }
            }

            $stats['inserted']['big_rocks'] = $insertedCount;
            $report->stepOk($step.' migrated: '.$insertedCount);

            return $map;
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateDailyEntries($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Daily Entries';
        try {
            $rows = $a->table('daily_entries')->orderBy('id')->get();

            $settings = $a->table('report_settings')->where('is_active', true)->orderByDesc('id')->first();
            $planClose = $settings ? $this->normalizeTime((string) ($settings->plan_close_rule ?: '10:00')) : '10:00';
            $realClose = $settings ? $this->normalizeTime((string) ($settings->realization_close_rule ?: '23:00')) : '23:00';

            $insert = [];
            foreach ($rows as $r) {
                $date = Carbon::parse($r->entry_date)->toDateString();
                $insert[] = [
                    'id' => (int) $r->id,
                    'user_id' => (int) $r->user_id,
                    'entry_date' => $date,
                    'plan_text' => null,
                    'plan_status' => $this->mapReportingStatus((string) $r->plan_status, $r->plan_submitted_at, $date, $planClose),
                    'plan_submitted_at' => $r->plan_submitted_at,
                    'realization_text' => null,
                    'realization_status' => $this->mapReportingStatus((string) $r->realization_status, $r->realization_submitted_at, $date, $realClose),
                    'realization_submitted_at' => $r->realization_submitted_at,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                    'plan_title' => null,
                    'plan_relation_reason' => null,
                    'realization_notes' => null,
                    'realization_reason' => null,
                    'big_rock_id' => null,
                    'roadmap_item_id' => null,
                ];
            }

            if (! empty($insert)) {
                $b->table('daily_entries')->insert($insert);
            }

            $stats['inserted']['daily_entries'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function mapReportingStatus(string $aStatus, $submittedAt, string $date, string $closeTime): string
    {
        $aStatus = trim($aStatus);
        $hasSubmission = $submittedAt !== null;

        if ($hasSubmission) {
            try {
                $close = Carbon::parse($date.' '.$closeTime);
                $sub = Carbon::parse($submittedAt);
                return $sub->gt($close) ? 'late' : 'submitted';
            } catch (\Throwable) {
                return 'submitted';
            }
        }

        if ($aStatus === 'closed') {
            return 'missing';
        }

        return 'draft';
    }

    /**
     * @param array<int, array<int, int>> $bigRockMapByUser
     * @return array{0: array<int,int>, 1: array{merged_pairs:int,plan_only:int,real_only:int,both_in_one_row:int}}
     */
    private function migrateDailyEntryItemsWithPlanRealizationPairing($a, $b, array &$stats, MigrationReport $report, array $opts, array $bigRockMapByUser): array
    {
        $step = 'Daily Entry Items (pair plan+real)';
        try {
            $entries = $a->table('daily_entries')->orderBy('id')->get(['id', 'user_id', 'entry_date']);
            $entryById = [];
            foreach ($entries as $e) {
                $entryById[(int) $e->id] = $e;
            }

            $items = $a->table('daily_entry_items')->orderBy('daily_entry_id')->orderBy('id')->get();

            $itemsByEntry = [];
            foreach ($items as $it) {
                $itemsByEntry[(int) $it->daily_entry_id][] = $it;
            }

            $itemIdMap = [];
            $insert = [];

            $mergedPairs = 0;
            $planOnly = 0;
            $realOnly = 0;
            $bothInOneRow = 0;

            foreach ($itemsByEntry as $dailyEntryId => $rows) {
                $entry = $entryById[$dailyEntryId] ?? null;
                if (! $entry) {
                    foreach ($rows as $it) {
                        $itemIdMap[(int) $it->id] = (int) $it->id;
                    }
                    continue;
                }

                $userId = (int) $entry->user_id;

                $grouped = [];
                foreach ($rows as $it) {
                    $key = (string) ($it->work_type ?: '');
                    $key .= '|';
                    $key .= (string) ($it->big_rock_id ?: 'null');
                    $grouped[$key][] = $it;
                }

                foreach ($grouped as $bucket) {
                    $plans = [];
                    $reals = [];
                    $both = [];

                    foreach ($bucket as $it) {
                        $hasPlan = $it->planned_hours !== null;
                        $hasReal = $it->realized_hours !== null;

                        if ($hasPlan && $hasReal) {
                            $both[] = $it;
                        } elseif ($hasPlan) {
                            $plans[] = $it;
                        } elseif ($hasReal) {
                            $reals[] = $it;
                        } else {
                            $plans[] = $it;
                        }
                    }

                    usort($plans, fn ($x, $y) => strcmp((string) ($x->created_at ?? ''), (string) ($y->created_at ?? '')));
                    usort($reals, fn ($x, $y) => strcmp((string) ($x->created_at ?? ''), (string) ($y->created_at ?? '')));
                    usort($both, fn ($x, $y) => strcmp((string) ($x->created_at ?? ''), (string) ($y->created_at ?? '')));

                    foreach ($both as $it) {
                        $bothInOneRow++;
                        $newId = (int) $it->id;
                        $itemIdMap[(int) $it->id] = $newId;

                        $planTitle = $this->makeTitleFromText((string) $it->description);
                        $planText = (string) $it->description;

                        $bigRockId = null;
                        if ($it->big_rock_id) {
                            $bigRockId = $bigRockMapByUser[$userId][(int) $it->big_rock_id] ?? null;
                        }

                        $insert[] = [
                            'id' => $newId,
                            'daily_entry_id' => $dailyEntryId,
                            'big_rock_id' => $bigRockId,
                            'roadmap_item_id' => null,
                            'plan_title' => $planTitle,
                            'plan_text' => $planText,
                            'plan_relation_reason' => $this->pickRelationReason($it->notes, $opts['default_relation_reason']),
                            'realization_status' => 'done',
                            'realization_text' => $planText,
                            'realization_reason' => null,
                            'realization_attachment_path' => null,
                            'created_at' => $it->created_at,
                            'updated_at' => $it->updated_at,
                        ];
                    }

                    $pairs = min(count($plans), count($reals));
                    for ($i = 0; $i < $pairs; $i++) {
                        $p = $plans[$i];
                        $r = $reals[$i];
                        $mergedPairs++;

                        $newId = (int) $p->id;
                        $itemIdMap[(int) $p->id] = $newId;
                        $itemIdMap[(int) $r->id] = $newId;

                        $sourceForTitle = (string) ($p->description ?: $r->description);
                        $planTitle = $this->makeTitleFromText($sourceForTitle);

                        $bigRockId = null;
                        $brOld = $p->big_rock_id ?: $r->big_rock_id;
                        if ($brOld) {
                            $bigRockId = $bigRockMapByUser[$userId][(int) $brOld] ?? null;
                        }

                        $insert[] = [
                            'id' => $newId,
                            'daily_entry_id' => $dailyEntryId,
                            'big_rock_id' => $bigRockId,
                            'roadmap_item_id' => null,
                            'plan_title' => $planTitle,
                            'plan_text' => (string) ($p->description ?: ''),
                            'plan_relation_reason' => $this->pickRelationReason($p->notes, $opts['default_relation_reason']),
                            'realization_status' => 'done',
                            'realization_text' => (string) ($r->description ?: ''),
                            'realization_reason' => null,
                            'realization_attachment_path' => null,
                            'created_at' => $p->created_at ?: $r->created_at,
                            'updated_at' => $r->updated_at ?: $p->updated_at,
                        ];
                    }

                    for ($i = $pairs; $i < count($plans); $i++) {
                        $p = $plans[$i];
                        $planOnly++;
                        $newId = (int) $p->id;
                        $itemIdMap[(int) $p->id] = $newId;

                        $bigRockId = null;
                        if ($p->big_rock_id) {
                            $bigRockId = $bigRockMapByUser[$userId][(int) $p->big_rock_id] ?? null;
                        }

                        $insert[] = [
                            'id' => $newId,
                            'daily_entry_id' => $dailyEntryId,
                            'big_rock_id' => $bigRockId,
                            'roadmap_item_id' => null,
                            'plan_title' => $this->makeTitleFromText((string) $p->description),
                            'plan_text' => (string) ($p->description ?: ''),
                            'plan_relation_reason' => $this->pickRelationReason($p->notes, $opts['default_relation_reason']),
                            'realization_status' => 'draft',
                            'realization_text' => null,
                            'realization_reason' => null,
                            'realization_attachment_path' => null,
                            'created_at' => $p->created_at,
                            'updated_at' => $p->updated_at,
                        ];
                    }

                    for ($i = $pairs; $i < count($reals); $i++) {
                        $r = $reals[$i];
                        $realOnly++;
                        $newId = (int) $r->id;
                        $itemIdMap[(int) $r->id] = $newId;

                        $bigRockId = null;
                        if ($r->big_rock_id) {
                            $bigRockId = $bigRockMapByUser[$userId][(int) $r->big_rock_id] ?? null;
                        }

                        $text = (string) ($r->description ?: '');
                        $title = $opts['map_real_only_plan_title_prefix'].$this->makeTitleFromText($text);

                        $insert[] = [
                            'id' => $newId,
                            'daily_entry_id' => $dailyEntryId,
                            'big_rock_id' => $bigRockId,
                            'roadmap_item_id' => null,
                            'plan_title' => $title,
                            'plan_text' => $text,
                            'plan_relation_reason' => $opts['default_relation_reason'],
                            'realization_status' => 'done',
                            'realization_text' => $text,
                            'realization_reason' => null,
                            'realization_attachment_path' => null,
                            'created_at' => $r->created_at,
                            'updated_at' => $r->updated_at,
                        ];
                    }
                }
            }

            if (! empty($insert)) {
                $b->table('daily_entry_items')->insert($insert);
            }

            $stats['inserted']['daily_entry_items'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));

            return [
                $itemIdMap,
                [
                    'merged_pairs' => $mergedPairs,
                    'plan_only' => $planOnly,
                    'real_only' => $realOnly,
                    'both_in_one_row' => $bothInOneRow,
                ],
            ];
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function pickRelationReason($notes, string $fallback): string
    {
        $n = trim((string) ($notes ?? ''));
        return $n !== '' ? $n : $fallback;
    }

    private function makeTitleFromText(string $text): string
    {
        $t = trim($text);
        if ($t === '') {
            return '(Migrasi) Item';
        }
        $firstLine = preg_split("/\\r\\n|\\r|\\n/", $t)[0] ?? $t;
        $firstLine = trim($firstLine);
        $firstLine = Str::limit($firstLine, 255, '');
        return $firstLine !== '' ? $firstLine : Str::limit($t, 255, '');
    }

    /**
     * @param array<int,int> $itemIdMap old_item_id => new_item_id
     */
    private function migrateAttachments($a, $b, array &$stats, MigrationReport $report, string $attachmentsRoot, array $itemIdMap): void
    {
        $step = 'Attachments (copy files + insert rows)';
        try {
            $rows = $a->table('daily_entry_item_attachments')->orderBy('id')->get();
            $insert = [];

            $root = rtrim($attachmentsRoot, DIRECTORY_SEPARATOR);
            $diskRoot = storage_path('app/private');

            foreach ($rows as $r) {
                $oldItemId = (int) $r->daily_entry_item_id;
                $newItemId = $itemIdMap[$oldItemId] ?? $oldItemId;

                $srcRel = (string) $r->file_path;
                $srcFull = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $srcRel);

                if (! file_exists($srcFull)) {
                    $stats['attachments']['missing']++;
                    $stats['warnings'][] = "Missing file: {$srcRel}";
                    continue;
                }

                $baseName = basename(str_replace('\\', '/', $srcRel));
                if ($baseName === '' || $baseName === '.' || $baseName === '..') {
                    $baseName = (string) ($r->file_name ?: ('file-'.$r->id));
                }

                $destRelDir = 'daily-entry-attachments/'.$newItemId;
                $destDir = $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $destRelDir);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }

                $destRel = $destRelDir.'/'.$baseName;
                $destFull = $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $destRel);

                if (file_exists($destFull)) {
                    $stats['attachments']['renamed_due_to_collision']++;
                    $destRel = $destRelDir.'/'.$this->uniqueName($destDir, $baseName);
                    $destFull = $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $destRel);
                }

                if (! copy($srcFull, $destFull)) {
                    $stats['attachments']['skipped']++;
                    $stats['warnings'][] = "Gagal copy file: {$srcRel}";
                    continue;
                }

                $stats['attachments']['copied']++;

                $insert[] = [
                    'id' => (int) $r->id,
                    'daily_entry_item_id' => $newItemId,
                    'path' => $destRel,
                    'original_name' => $r->file_name,
                    'mime_type' => $r->file_mime,
                    'size_bytes' => $r->file_size,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('daily_entry_item_attachments')->insert($insert);
            }

            $stats['inserted']['daily_entry_item_attachments'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert).' (files copied: '.$stats['attachments']['copied'].')');
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function uniqueName(string $dir, string $baseName): string
    {
        $name = pathinfo($baseName, PATHINFO_FILENAME);
        $ext = pathinfo($baseName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? '.'.$ext : '';

        for ($i = 2; $i < 200; $i++) {
            $candidate = $name.'-'.$i.$ext;
            if (! file_exists($dir.DIRECTORY_SEPARATOR.$candidate)) {
                return $candidate;
            }
        }

        return $name.'-'.Str::random(6).$ext;
    }

    private function backfillLegacyAttachmentPath($b, array &$stats, MigrationReport $report): void
    {
        $step = 'Legacy attachment path backfill';
        try {
            $rows = $b->table('daily_entry_item_attachments')
                ->selectRaw('daily_entry_item_id, min(id) as min_id')
                ->groupBy('daily_entry_item_id')
                ->get();

            $updated = 0;
            foreach ($rows as $r) {
                $att = $b->table('daily_entry_item_attachments')->where('id', (int) $r->min_id)->first(['path']);
                if (! $att) {
                    continue;
                }
                $updated += (int) $b->table('daily_entry_items')
                    ->where('id', (int) $r->daily_entry_item_id)
                    ->whereNull('realization_attachment_path')
                    ->update(['realization_attachment_path' => (string) $att->path]);
            }

            $stats['updated']['daily_entry_items.realization_attachment_path'] = $updated;
            $report->stepOk($step.' updated rows: '.$updated);
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateDiscordNotificationsToNotificationLogs($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Discord Notifications → Notification Logs';
        try {
            $rows = $a->table('discord_notifications')->orderBy('id')->get();
            $insert = [];
            foreach ($rows as $r) {
                $status = strtolower(trim((string) $r->status));
                $mapped = $status === 'sent' ? 'sent' : ($status === 'failed' ? 'failed' : 'skipped');

                $message = (string) ($r->message ?? '');
                $insert[] = [
                    'id' => (int) $r->id,
                    'channel' => 'discord',
                    'type' => 'daily_summary',
                    'context_date' => $r->reporting_date,
                    'status' => $mapped,
                    'summary' => Str::limit($message, 255, ''),
                    'payload' => json_encode([
                        'message' => $message,
                        'counts' => [
                            'divisions' => (int) ($r->divisions_count ?? 0),
                            'people' => (int) ($r->people_count ?? 0),
                            'findings' => (int) ($r->findings_count ?? 0),
                        ],
                        'attempt_count' => (int) ($r->attempt_count ?? 0),
                    ]),
                    'error_message' => $r->error_message,
                    'sent_at' => $r->sent_at,
                    'failed_at' => $r->failed_at,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('notification_logs')->insert($insert);
            }

            $stats['inserted']['notification_logs'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function migrateAdminOverridesToOverrideLogs($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Admin Overrides → Override Logs';
        try {
            $rows = $a->table('admin_overrides')->orderBy('id')->get();
            $insert = [];
            foreach ($rows as $r) {
                $changes = [
                    'before' => [$r->field => $r->old_value],
                    'after' => [$r->field => $r->new_value],
                ];
                $contextDate = null;
                try {
                    $contextDate = $r->overridden_at ? Carbon::parse($r->overridden_at)->toDateString() : null;
                } catch (\Throwable) {
                    $contextDate = null;
                }

                $insert[] = [
                    'id' => (int) $r->id,
                    'actor_user_id' => (int) $r->admin_user_id,
                    'target_type' => (string) ($r->target_type ?: 'unknown'),
                    'target_id' => (int) $r->target_id,
                    'context_date' => $contextDate,
                    'reason' => (string) ($r->reason ?: ''),
                    'changes' => json_encode($changes),
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            }

            if (! empty($insert)) {
                $b->table('override_logs')->insert($insert);
            }

            $stats['inserted']['override_logs'] = count($insert);
            $report->stepOk($step.' migrated: '.count($insert));
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function resetSequences($b, array &$stats, MigrationReport $report): void
    {
        $step = 'Reset Sequences';
        try {
            $tables = [
                'users',
                'divisions',
                'hod_assignments',
                'report_settings',
                'big_rocks',
                'daily_entries',
                'daily_entry_items',
                'daily_entry_item_attachments',
                'notification_logs',
                'override_logs',
            ];

            foreach ($tables as $t) {
                $max = (int) ($b->table($t)->max('id') ?? 0);
                $sql = "select setval(pg_get_serial_sequence('{$t}','id'), {$max}, true)";
                try {
                    $b->select($sql);
                } catch (\Throwable) {
                    // ignore if table has no sequence / id is not serial
                }
            }

            $report->stepOk($step.' done');
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }

    private function recomputeMetrics($a, $b, array &$stats, MigrationReport $report): void
    {
        $step = 'Recompute Findings + Health Scores';
        try {
            $min = $a->table('daily_entries')->min('entry_date');
            $max = $a->table('daily_entries')->max('entry_date');
            if (! $min || ! $max) {
                $report->stepOk($step.' skipped (no daily_entries)');
                return;
            }

            $from = Carbon::parse($min);
            $to = Carbon::parse($max);
            app(MetricsService::class)->compute($from, $to);

            $stats['inserted']['findings'] = (int) $b->table('findings')->count();
            $stats['inserted']['health_scores'] = (int) $b->table('health_scores')->count();

            $report->stepOk($step.' computed for '.$from->toDateString().' → '.$to->toDateString());
        } catch (\Throwable $e) {
            $report->stepFail($step, $e);
            throw $e;
        }
    }
}

class MigrationReport
{
    private string $path;
    private array $lines = [];

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function h1(string $title): void
    {
        $this->lines[] = '# '.$title;
        $this->lines[] = '';
    }

    public function h2(string $title): void
    {
        $this->lines[] = '## '.$title;
        $this->lines[] = '';
    }

    /**
     * @param array<string,string> $kv
     */
    public function kv(array $kv): void
    {
        foreach ($kv as $k => $v) {
            $this->lines[] = "- {$k}: {$v}";
        }
        $this->lines[] = '';
    }

    public function bullet(string $text): void
    {
        $this->lines[] = '- '.$text;
    }

    public function blank(): void
    {
        $this->lines[] = '';
    }

    public function stepOk(string $message): void
    {
        $this->lines[] = '- [OK] '.$message;
    }

    public function stepFail(string $step, \Throwable $e): void
    {
        $this->lines[] = '- [FAIL] '.$step.': '.$e->getMessage();
        $this->lines[] = '';
        $this->lines[] = '```';
        $this->lines[] = $e->getTraceAsString();
        $this->lines[] = '```';
        $this->lines[] = '';
    }

    public function tableAssoc(array $assoc, string $title): void
    {
        $this->lines[] = '**'.$title.'**';
        $this->lines[] = '';
        $this->lines[] = '| Key | Value |';
        $this->lines[] = '|---|---:|';
        foreach ($assoc as $k => $v) {
            $this->lines[] = '| '.$k.' | '.$v.' |';
        }
        $this->lines[] = '';
    }

    public function write(): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($this->path, implode(PHP_EOL, $this->lines).PHP_EOL);
    }
}
