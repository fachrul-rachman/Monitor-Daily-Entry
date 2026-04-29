@props(['paginator'])

@php
    $prev = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator ? $paginator->previousPageUrl() : null;
    $next = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator ? $paginator->nextPageUrl() : null;

    $normalizeUrl = function ($u) {
        if (! is_string($u) || $u === '') {
            return null;
        }

        // Already absolute (http/https).
        if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            return $u;
        }

        // Ensure root-relative so browser doesn't append it to current path (e.g. /admin + admin/users).
        if (! str_starts_with($u, '/')) {
            $u = '/'.$u;
        }

        return $u;
    };

    $prevHref = $normalizeUrl($prev);
    $nextHref = $normalizeUrl($next);
@endphp

<div>
    @if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
        <div class="flex items-center justify-between gap-3 text-sm text-muted">
            <p>
                Menampilkan
                <span class="font-medium text-text">
                    {{ method_exists($paginator, 'firstItem') && $paginator->firstItem() ? $paginator->firstItem() : 1 }}
                    &ndash;
                    {{ method_exists($paginator, 'lastItem') && $paginator->lastItem() ? $paginator->lastItem() : $paginator->count() }}
                </span>
                dari
                <span class="font-medium text-text">
                    {{ method_exists($paginator, 'total') ? $paginator->total() : $paginator->count() }}
                </span>
                data
            </p>

            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if($paginator->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-border bg-app-bg text-muted cursor-not-allowed">
                        Sebelumnya
                    </span>
                @else
                    <a
                        href="{{ $prevHref }}"
                        class="px-3 py-1.5 rounded-lg border border-border bg-surface hover:bg-app-bg text-text transition-colors"
                    >
                        Sebelumnya
                    </a>
                @endif

                {{-- Next --}}
                @if($paginator->hasMorePages())
                    <a
                        href="{{ $nextHref }}"
                        class="px-3 py-1.5 rounded-lg border border-border bg-surface hover:bg-app-bg text-text transition-colors"
                    >
                        Selanjutnya
                    </a>
                @else
                    <span class="px-3 py-1.5 rounded-lg border border-border bg-app-bg text-muted cursor-not-allowed">
                        Selanjutnya
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>
