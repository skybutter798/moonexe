<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>Announcements</x-slot:pageTitle>

  <x-slot:headerFiles>
  <style>
    .announcement-header {
      cursor: pointer;
      background-color: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 10px 15px;
      transition: background-color 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: nowrap;
    }

    .announcement-header:hover {
      background-color: #e9ecef;
    }

    .announcement-header strong {
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-weight: 600;
      color: #333;
    }

    .announcement-header small {
      flex-shrink: 0;
      color: #666;
      font-size: 0.875rem;
      white-space: nowrap;
    }

    .announcement-content {
      display: none;
      border-left: 3px solid #0d6efd;
      background-color: #fff;
      margin-bottom: 10px;
      padding: 15px;
      border-radius: 0 0 6px 6px;
    }

    .announcement-banner {
      max-width: 100%;
      height: auto;
      margin-bottom: 10px;
      border-radius: 6px;
    }

    /* Optional: spacing for multiple announcements */
    #announcementList > div {
      margin-bottom: 6px;
    }

    /* 🌐 Mobile-friendly adjustments */
    @media (max-width: 576px) {
      .announcement-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
      }

      .announcement-header strong {
        white-space: normal;     /* allow wrapping */
        overflow: visible;
        text-overflow: unset;
      }

      .announcement-header small {
        align-self: flex-end;
        font-size: 0.8rem;
        color: #888;
      }
    }
  </style>
</x-slot:headerFiles>

  <div class="container py-4">
    <h1 class="mb-4">Announcements Board</h1>

    @if($announcements->isEmpty())
      <div class="alert alert-info">No announcements available.</div>
    @else
      <div id="announcementList">
        @foreach($announcements as $announcement)
          <div>
            <div class="announcement-header" data-id="{{ $announcement->id }}">
              <strong>{{ $announcement->name }}</strong>
              <small>{{ $announcement->created_at->format('Y-m-d') }}</small>
            </div>

            <div class="announcement-content" id="content-{{ $announcement->id }}">
              {!! $announcement->content !!}
              <small class="text-muted d-block mt-2">{{ $announcement->created_at->diffForHumans() }}</small>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <x-slot:footerFiles>
    <script>
      document.querySelectorAll('.announcement-header').forEach(header => {
        header.addEventListener('click', () => {
          const id = header.dataset.id;
          const content = document.getElementById('content-' + id);
          content.style.display = (content.style.display === 'block') ? 'none' : 'block';
        });
      });
    </script>
  </x-slot:footerFiles>
</x-base-layout>