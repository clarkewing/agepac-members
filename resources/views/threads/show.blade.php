@extends('threads._layout')

@section('main')
    <thread-view :thread="{{ $thread }}" inline-template>
        <div>
            @include('threads._title-header')

            <posts @added="repliesCount++" @removed="repliesCount--"></posts>
        </div>
    </thread-view>
@endsection
