<li class="mb-2">
  <div class="flex items-center space-x-2">
    @if($user->children)
      {{-- only show a button if there *are* children --}}
      <button class="toggle-btn focus:outline-none">
        <i class="bi bi-plus-circle-fill"></i>
      </button>
    @else
      {{-- keep spacing aligned when there’s no button --}}
      <span class="w-5"></span>
    @endif

    <span class="font-medium">{{ $user->name }}</span>
  </div>

  @if($user->children)
    {{-- start hidden; we’ll un‐hide in JS --}}
    <ul class="ml-6 border-l pl-4 hidden">
      @foreach($user->children as $child)
        @include('admin.referrals.partials.node', ['user' => $child])
      @endforeach
    </ul>
  @endif
</li>
