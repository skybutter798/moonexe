<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>Referrals Tree</x-slot:pageTitle>

  <x-slot:headerFiles>
    {{-- your custom treeview styles --}}
    @vite([
      'resources/scss/light/assets/elements/custom-tree_view.scss',
      'resources/scss/dark/assets/elements/custom-tree_view.scss',
    ])
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    >

    <style>
      /* basic tree reset & spacing */
      .treeview { list-style: none; padding: 0; margin: 0; }
      .treeview .tv-item { margin-bottom: 0.5rem; }
      .treeview .tv-header {
        display: flex; align-items: center;
        padding: 0.4rem 0.6rem; border-radius: 0.25rem;
        transition: background-color 0.2s;
      }
      .treeview .tv-header:hover {
        background-color: rgba(0,0,0,0.05);
      }

      /* collapsible handle */
      .tv-collapsible {
        display: flex; align-items: center;
        cursor: pointer; margin-right: 0.5rem;
      }
      .tv-collapsible .icon {
        transition: transform 0.2s ease;
      }
      .tv-collapsible .icon i { font-size: 1rem; }

      /* rotate when “open” */
      .tv-collapsible.open .icon {
        transform: rotate(90deg);
      }

      /* hide/show children */
      .treeview-collapse { display: none; margin-left: 1.75rem; border-left: 1px solid #e2e8f0; padding-left: 0.75rem; }
      .treeview-collapse.show { display: block; }
      .treeview .treeview .tv-item:not(.tv-folder) {
            padding-left: 0px;
        }
    </style>
  </x-slot:headerFiles>

  @php
    // Recursive renderer for the $tree passed from the controller
    $render = function(array $nodes) use (&$render) {
      echo '<ul class="treeview">';
      foreach ($nodes as $n) {
        $hasKids = count($n->children) > 0;
        echo '<li class="tv-item'.($hasKids?' tv-folder':'').'">';
          echo '<div class="tv-header">';
            // every node gets a .tv-collapsible wrapper
            echo '<div class="tv-collapsible'.($hasKids?'':' disabled').'"'
                .($hasKids ? " data-target=\"node-{$n->id}\"" : '')
                .'>';
              echo '<div class="icon"><i class="bi bi-chevron-right"></i></div>';
              echo '<p class="title">'.e($n->name).'</p>';
            echo '</div>';
          echo '</div>';

          // only render children container if there are kids
          if ($hasKids) {
            echo "<div id=\"node-{$n->id}\" class=\"treeview-collapse\">";
              $render($n->children);
            echo '</div>';
          }
        echo '</li>';
      }
      echo '</ul>';
    };
  @endphp

  <div class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Referral Hierarchy</h2>
    @php $render($tree); @endphp
  </div>

  <x-slot:footerFiles>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.tv-collapsible').forEach(btn => {
          btn.addEventListener('click', () => {
            // toggle the “open” class on the handle
            btn.classList.toggle('open');
            // toggle the show class on the matching children container
            const pane = document.getElementById(btn.dataset.target);
            if (pane) pane.classList.toggle('show');
          });
        });
      });
    </script>
  </x-slot:footerFiles>
</x-base-layout>
