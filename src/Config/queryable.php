<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Query Key Names
    |--------------------------------------------------------------------------
    |
    | You can change the name of the query parameter keys here if you have a
    | reserved word for other queries or prefer something else.
    |
    | These names are used in triggering whether or not to run search or filters
    | on an HTTP call. For example:
    \
    | http://localhost?search=someSearchTerm will trigger the search as long as
    | the 'search' query parameter is present.
    |
    | http://localhost?filters=firstname=test will trigger the filters as long as
    | the 'filters' query parameter is present.
    |
    */

    'searchKeyName' => 'search',
    'filterKeyName' => 'filter',

    /*
    |--------------------------------------------------------------------------
    | Default OrderBy Direction
    |--------------------------------------------------------------------------
    |
    | The default orderBy direction when the orderBy query attribute has been
    | defined without a direction.
    |
    */

    'defaultOrdering' => 'asc',

];
