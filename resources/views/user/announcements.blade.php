<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Announcements
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <!-- If you have any CSS for cards or modals, include here -->
  </x-slot:headerFiles>

  <div class="container py-4">
    <h1 class="mb-4">Announcements Board</h1>

    @if($announcements->isEmpty())
      <div class="alert alert-info">
        There are no announcements at the moment.
      </div>
    @else
      <div class="row">
        @foreach($announcements as $announcement)
          <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                  <h5 class="mb-0 text-white">{{ $announcement->name }}</h5>
                  <small class="text-white-50">
                    {{ $announcement->created_at->format('Y-m-d') }}
                  </small>
                </div>

                <div class="card-body text-black">
                    {!! nl2br(e($announcement->content)) !!}
                </div>
                <div class="card-footer text-muted small d-flex justify-content-between">
                    <span>{{ $announcement->created_at->diffForHumans() }}</span>
                </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <x-slot:footerFiles>
    <!-- If you need any JS (e.g. expand/collapse) add here -->
  </x-slot:footerFiles>
</x-base-layout>
