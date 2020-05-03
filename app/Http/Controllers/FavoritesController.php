<?php

namespace App\Http\Controllers;

use App\Reply;

class FavoritesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Reply  $reply
     * @return \Illuminate\Http\Response
     */
    public function store(Reply $reply)
    {
        $reply->favorite();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reply  $reply
     * @return void
     */
    public function destroy(Reply $reply): void
    {
        $reply->unfavorite();
    }
}
