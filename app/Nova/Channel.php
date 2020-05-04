<?php

namespace App\Nova;

use Drobee\NovaSluggable\Slug;
use Drobee\NovaSluggable\SluggableText;
use Gkermer\TextAutoComplete\TextAutoComplete;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Channel extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Forum';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Channel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
    ];

    /**
     * Override model global scopes.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->withoutGlobalScopes(['active', 'alphabetized']);
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->asBigInt()->sortable(),
            TextAutoComplete::make('Parent')
                ->items(\App\Channel::pluck('parent')),
            SluggableText::make('Name')
                ->rules('required')
                ->creationRules('unique:channels,name')
                ->updateRules('unique:channels,name,{{resourceId}}')
                ->sortable(),
            Slug::make('Slug')
                ->hideFromIndex()
                ->rules('required')
                ->creationRules('unique:channels,slug')
                ->updateRules('unique:channels,slug,{{resourceId}}')
                ->help('Used for the channel URL')
                ->slugModel(static::$model),
            Text::make('Description')
                ->rules('required'),
            Boolean::make('Archived')
                ->onlyOnForms()->hideWhenCreating(),
            Boolean::make('Visible', function () {
                return ! $this->archived;
            })->exceptOnForms(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
