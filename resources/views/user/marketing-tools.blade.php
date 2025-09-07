<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        {{ $title }}
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/pages/faq.scss']) {{-- reuse base styles if you like --}}
        @vite(['resources/scss/dark/assets/pages/faq.scss'])
        <style>
            .mt-wrapper { margin-top: 1.25rem; }

            .mt-header {
                display: flex; align-items: center; justify-content: space-between;
                gap: 16px; flex-wrap: wrap; margin-bottom: 1rem;
            }
            .mt-search {
                flex: 1 1 320px; display: flex; align-items: center; gap: .5rem;
                border: 1px solid var(--bs-border-color, #e5e7eb);
                border-radius: .5rem; padding: .5rem .75rem;
                background: var(--bs-body-bg, #fff);
            }
            .mt-search input {
                border: none; outline: none; width: 100%; background: transparent;
            }
            .mt-filters { display: flex; gap: .5rem; flex-wrap: wrap; }
            .mt-chip {
                border: 1px solid var(--bs-border-color, #e5e7eb);
                background: var(--bs-body-bg, #fff);
                padding: .35rem .65rem; border-radius: 999px; cursor: pointer;
                font-size: .875rem; line-height: 1; user-select: none;
            }
            .mt-chip.active { background: var(--bs-primary, #3b82f6); color: #fff; border-color: transparent; }

            /* Accordions like FAQ but card grid inside */
            .mt-accordion .card { border-radius: .5rem; overflow: hidden; border: 1px solid var(--bs-border-color,#e5e7eb); }
            .mt-accordion .card-header { cursor: pointer; padding: .9rem 1rem; display: flex; align-items: center; justify-content: space-between; background: var(--bs-body-bg,#fff); }
            .mt-accordion .card-header h5 { margin: 0; font-size: 1rem; font-weight: 600; }
            .mt-accordion .card-body { padding: 1rem; background: var(--bs-body-bg,#fff); }

            .mt-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 12px;
            }
            @media (min-width: 576px) { .mt-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 992px) { .mt-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

            .mt-item {
                border: 1px solid var(--bs-border-color,#e5e7eb);
                border-radius: .75rem; overflow: hidden; background: var(--bs-body-bg,#fff);
                display: flex; flex-direction: column; min-height: 100%;
            }
            .mt-thumb {
                position: relative; padding-top: 56.25%; overflow: hidden; background: #f6f7f9;
            }
            .mt-thumb img {
                position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
            }
            .mt-content { padding: .75rem .9rem; display: flex; flex-direction: column; gap: .35rem; }
            .mt-title { font-weight: 600; font-size: .98rem; }
            .mt-desc { font-size: .875rem; color: var(--bs-secondary-color,#6b7280); }
            .mt-tags { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .2rem; }
            .mt-tag { font-size: .75rem; background: #eef2ff; color: #4338ca; padding: .2rem .45rem; border-radius: .35rem; }

            .mt-actions { display: flex; gap: .5rem; margin-top: .6rem; }
            .mt-btn {
                display: inline-flex; align-items: center; gap: .4rem; border-radius: .5rem;
                padding: .45rem .7rem; border: 1px solid var(--bs-border-color,#e5e7eb); text-decoration: none;
                font-size: .875rem; color: inherit;
            }
            .mt-btn.primary { background: var(--bs-primary,#3b82f6); color: #fff; border-color: transparent; }
            .mt-btn i { font-size: 1rem; line-height: 1; }

            /* Rotate chevron on open */
            .mt-accordion .card-header .chev { transition: transform .2s ease; }
            .mt-accordion .card-header[aria-expanded="true"] .chev { transform: rotate(180deg); }

            /* Hide on filter */
            .is-hidden { display: none !important; }
        </style>
    </x-slot>

    <div class="faq"> {{-- reuse wrapper spacing --}}
        <div class="faq-layouting layout-spacing">
            <div class="fq-tab-section">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Marketing <span>Tools</span></h2>

                        {{-- Header: search + filters --}}
                        <div class="mt-header">
                            <div class="mt-search">
                                <i class="bi bi-search"></i>
                                <input id="mtSearch" type="text" placeholder="Search tools (title, tags, type)…">
                            </div>
                            <div class="mt-filters">
                                <button class="mt-chip active" data-type="all">All</button>
                                <button class="mt-chip" data-type="image">Images</button>
                                <button class="mt-chip" data-type="pdf">PDFs</button>
                            </div>
                        </div>

                        {{-- Accordions per section (like FAQ) --}}
                        <div class="mt-accordion" id="mt_sections">
                            @foreach ($tools as $sIndex => $section)
                                <div class="card mt-wrapper">
                                    <div class="card-header"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#mt_collapse_{{ $sIndex }}"
                                         role="button"
                                         aria-expanded="{{ $sIndex === 0 ? 'true' : 'false' }}"
                                         aria-controls="mt_collapse_{{ $sIndex }}">
                                        <h5>{{ $section['section'] }}</h5>
                                        <i class="bi bi-chevron-down chev"></i>
                                    </div>

                                    <div id="mt_collapse_{{ $sIndex }}"
                                         class="collapse {{ $sIndex === 0 ? 'show' : '' }}"
                                         data-bs-parent="#mt_sections">
                                        <div class="card-body">
                                            <div class="mt-grid">
                                                @foreach ($section['items'] as $item)
                                                    <div class="mt-item"
                                                         data-type="{{ $item['type'] }}"
                                                         data-title="{{ Str::lower($item['title']) }}"
                                                         data-tags="{{ Str::lower(implode(',', $item['tags'] ?? [])) }}">
                                                        <div class="mt-thumb">
                                                            @if(($item['type'] ?? '') === 'image')
                                                                <img src="{{ $item['thumb'] ?? $item['url'] }}" alt="{{ $item['title'] }}">
                                                            @elseif(($item['type'] ?? '') === 'pdf')
                                                                <img src="{{ $item['thumb'] ?? asset('img/pdf-thumb.png') }}" alt="{{ $item['title'] }}">
                                                            @else
                                                                <img src="{{ $item['thumb'] ?? asset('img/placeholder.png') }}" alt="{{ $item['title'] }}">
                                                            @endif
                                                        </div>
                                                        <div class="mt-content">
                                                            <div class="mt-title">{{ $item['title'] }}</div>
                                                            @if(!empty($item['desc']))
                                                                <div class="mt-desc">{{ $item['desc'] }}</div>
                                                            @endif

                                                            @if(!empty($item['tags']))
                                                                <div class="mt-tags">
                                                                    @foreach($item['tags'] as $t)
                                                                        <span class="mt-tag">{{ $t }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            <div class="mt-actions">
                                                                @if(($item['type'] ?? '') === 'image')
                                                                    <a href="#" class="mt-btn primary"
                                                                       data-open="image"
                                                                       data-url="{{ $item['url'] }}"
                                                                       data-title="{{ $item['title'] }}">
                                                                        <i class="bi bi-eye"></i> Preview
                                                                    </a>
                                                                    <a class="mt-btn" href="{{ $item['url'] }}" download>
                                                                        <i class="bi bi-download"></i> Download
                                                                    </a>
                                                                @elseif(($item['type'] ?? '') === 'pdf')
                                                                    <a href="#" class="mt-btn primary"
                                                                       data-open="pdf"
                                                                       data-url="{{ $item['url'] }}"
                                                                       data-title="{{ $item['title'] }}">
                                                                        <i class="bi bi-eye"></i> View PDF
                                                                    </a>
                                                                    <a class="mt-btn" href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                                                        <i class="bi bi-box-arrow-up-right"></i> Open
                                                                    </a>
                                                                @else
                                                                    <a class="mt-btn" href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                                                        <i class="bi bi-box-arrow-up-right"></i> Open
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div> {{-- grid --}}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div> {{-- accordion --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal (reusable) --}}
    <div class="modal fade" id="mtPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: .75rem;">
                <div class="modal-header">
                    <h5 class="modal-title" id="mtModalTitle">Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body p-0" id="mtModalBody" style="min-height: 60vh;">
                    {{-- Injected by JS --}}
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        <script>
            (function () {
                const chips = document.querySelectorAll('.mt-chip');
                const items = document.querySelectorAll('.mt-item');
                const search = document.getElementById('mtSearch');

                function applyFilter() {
                    const activeChip = document.querySelector('.mt-chip.active');
                    const type = activeChip?.dataset.type || 'all';
                    const q = (search.value || '').trim().toLowerCase();

                    items.forEach(el => {
                        const matchesType = (type === 'all') || (el.dataset.type === type);
                        const hay = (el.dataset.title + ' ' + el.dataset.tags).toLowerCase();
                        const matchesText = q === '' || hay.includes(q);
                        el.classList.toggle('is-hidden', !(matchesType && matchesText));
                    });
                }

                chips.forEach(c => c.addEventListener('click', () => {
                    chips.forEach(x => x.classList.remove('active'));
                    c.classList.add('active');
                    applyFilter();
                }));
                search.addEventListener('input', applyFilter);

                // Modal preview (image/pdf)
                const modalEl = document.getElementById('mtPreviewModal');
                const modalTitle = document.getElementById('mtModalTitle');
                const modalBody = document.getElementById('mtModalBody');
                let bsModal;

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-open]');
                    if (!btn) return;
                    e.preventDefault();

                    const kind = btn.dataset.open;
                    const url  = btn.dataset.url;
                    const ttl  = btn.dataset.title || 'Preview';

                    modalTitle.textContent = ttl;

                    if (kind === 'image') {
                        modalBody.innerHTML = `
                          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#0b0b0b;">
                            <img src="${url}" alt="${ttl}" style="max-width:100%;max-height:80vh;object-fit:contain;">
                          </div>`;
                    } else if (kind === 'pdf') {
                        // Use <iframe> for broader compatibility
                        modalBody.innerHTML = `
                          <iframe src="${url}#toolbar=1&navpanes=0&scrollbar=1"
                                  style="width:100%;height:80vh;border:0;"
                                  title="${ttl}"></iframe>`;
                    }

                    bsModal = bsModal || new bootstrap.Modal(modalEl);
                    bsModal.show();
                });

                // Initial filter (no search, "All")
                applyFilter();
            })();
        </script>
    </x-slot>
</x-base-layout>
