<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Announcements
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <!-- Optional: Add CSS here if needed -->
    <style>
      .announcement-content table {
        width: 100%;
        border-collapse: collapse;
      }
      .announcement-content th, .announcement-content td {
        border: 1px solid #ddd;
        padding: 8px;
      }
      .announcement-content th {
        background-color: #f2f2f2;
        font-weight: bold;
      }
    </style>
  </x-slot:headerFiles>

  <div class="container py-4">
    <h1 class="mb-4">Announcements Board</h1>
    
    {{-- Filter Form --}}
    <form method="GET" action="{{ route('user.annoucement') }}" class="d-flex flex-wrap align-items-end gap-2 mb-4">
        <div style="min-width: 160px;">
          <label class="form-label small mb-1">Start Date</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
        </div>
        <div style="min-width: 160px;">
          <label class="form-label small mb-1">End Date</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
        </div>
        <div>
          <button type="submit" class="btn btn-sm btn-primary mt-3">Search</button>
        </div>
        <div>
          <a href="{{ route('user.annoucement') }}" class="btn btn-sm btn-secondary mt-3">Reset</a>
        </div>
    </form>


    @if($announcements->isEmpty())
      <div class="alert alert-info">
        There are no announcements at the moment.
      </div>
    @else
      <div class="row">
        @foreach($announcements as $announcement)
          <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100">

              @if($announcement->banner_image)
                <img 
                  src="{{ asset('storage/' . $announcement->banner_image) }}" 
                  class="card-img-top" 
                  alt="Banner for {{ $announcement->name }}"
                  style="display: block; margin: 0 auto; max-width: 100%; height: auto; width: auto;">
              @endif

              <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">{{ $announcement->name }}</h5>
                <small class="text-white-50">
                  {{ $announcement->created_at->format('Y-m-d') }}
                </small>
              </div>

              <div class="card-body text-black announcement-content">
                {!! $announcement->content !!}
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
    <!-- Optional JS -->
  </x-slot:footerFiles>
</x-base-layout>
