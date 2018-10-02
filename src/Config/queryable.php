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
    | http://localhost?filters=on&firstname=test will trigger the filters as long as
    | the 'filters' query parameter is present and equal to 'on'
    |
    */

    'filterKeyName' => 'filters',

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
