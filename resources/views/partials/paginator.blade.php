@if ($paginator->hasPages())
    <nav class="d-flex justify-content-center" aria-label="Pagination">
        <ul class="pagination pagination-md mb-0 gap-2 align-items-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link d-flex align-items-center justify-content-center rounded-3 border-2" style="width: 40px; height: 40px; color: #adb5bd; background-color: #f8f9fa; border-color: #dee2e6;">
                        <i class="bi bi-chevron-left" style="font-size: 0.9rem; -webkit-text-stroke: 0.5px;"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link d-flex align-items-center justify-content-center rounded-3 border-2" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')" style="width: 40px; height: 40px; color: #495057; background-color: #fff; border-color: #dee2e6; transition: all 0.2s;">
                        <i class="bi bi-chevron-left" style="font-size: 0.9rem; -webkit-text-stroke: 0.5px;"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link d-flex align-items-center justify-content-center border-0" style="width: 40px; height: 40px; background: transparent; color: #6c757d;">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link d-flex align-items-center justify-content-center rounded-3 border-0 shadow-sm" style="width: 40px; height: 40px; background-color: var(--color-primary, #0d6efd); color: #fff; font-weight: bold;">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link d-flex align-items-center justify-content-center rounded-3 border-2" href="{{ $url }}" style="width: 40px; height: 40px; color: #495057; background-color: #fff; border-color: #dee2e6; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--color-primary, #0d6efd)'; this.style.color='var(--color-primary, #0d6efd)';" onmouseout="this.style.borderColor='#dee2e6'; this.style.color='#495057';">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link d-flex align-items-center justify-content-center rounded-3 border-2" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')" style="width: 40px; height: 40px; color: #495057; background-color: #fff; border-color: #dee2e6; transition: all 0.2s;">
                        <i class="bi bi-chevron-right" style="font-size: 0.9rem; -webkit-text-stroke: 0.5px;"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link d-flex align-items-center justify-content-center rounded-3 border-2" style="width: 40px; height: 40px; color: #adb5bd; background-color: #f8f9fa; border-color: #dee2e6;">
                        <i class="bi bi-chevron-right" style="font-size: 0.9rem; -webkit-text-stroke: 0.5px;"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
