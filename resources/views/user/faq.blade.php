<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        {{ $title }}
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/pages/faq.scss'])
        @vite(['resources/scss/dark/assets/pages/faq.scss'])
    </x-slot>

    <div class="faq">
        <div class="faq-layouting layout-spacing">
            <div class="fq-tab-section">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Frequently Asked <span>Questions</span></h2>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="accordion" id="faq_group_left">
                                    @foreach($faqs as $index => $faq)
                                        @if($index % 2 === 0)
                                            <div class="card">
                                                <div class="card-header" id="faq_heading_{{ $index }}">
                                                    <div class="mb-0" data-bs-toggle="collapse" role="navigation"
                                                         data-bs-target="#faq_collapse_{{ $index }}"
                                                         aria-expanded="false" aria-controls="faq_collapse_{{ $index }}">
                                                        <span class="faq-q-title">{{ $faq['q'] }}</span>
                                                        <div class="icons">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                 class="feather feather-chevron-down">
                                                                <polyline points="6 9 12 15 18 9"></polyline>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="faq_collapse_{{ $index }}" class="collapse"
                                                     aria-labelledby="faq_heading_{{ $index }}"
                                                     data-bs-parent="#faq_group_left">
                                                    <div class="card-body">
                                                        {!! $faq['a'] !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="accordion" id="faq_group_right">
                                    @foreach($faqs as $index => $faq)
                                        @if($index % 2 === 1)
                                            <div class="card">
                                                <div class="card-header" id="faq_heading_{{ $index }}">
                                                    <div class="mb-0" data-bs-toggle="collapse" role="navigation"
                                                         data-bs-target="#faq_collapse_{{ $index }}"
                                                         aria-expanded="false" aria-controls="faq_collapse_{{ $index }}">
                                                        <span class="faq-q-title">{{ $faq['q'] }}</span>
                                                        <div class="icons">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                 class="feather feather-chevron-down">
                                                                <polyline points="6 9 12 15 18 9"></polyline>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="faq_collapse_{{ $index }}" class="collapse"
                                                     aria-labelledby="faq_heading_{{ $index }}"
                                                     data-bs-parent="#faq_group_right">
                                                    <div class="card-body">
                                                        {!! $faq['a'] !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- No Bootstrap.bundle needed — your theme handles collapse --}}
    </x-slot>

</x-base-layout>
