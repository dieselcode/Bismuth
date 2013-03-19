<?php

include '../vendor/autoload.php';

try {
    $github = new \Bismuth\Endpoint\GitHub(
        new \Bismuth\Core\Auth\Basic('<username>', '<password>'),
        new \Bismuth\Core\Cache\FileSystem(['cacheMaxSize' => 2097152])
    );

    $api = $github
            ->user()                    // user object
            ->current()                 // current user (logged in)
            ->repos()                   // repository object
            ->list([                    // repository list (with options)
                'sort' => 'full_name',
                'direction' => 'asc'
            ]);

    /*
    $user   = $github->user()->current();
    $repos  = $user->repos()->list(['sort' => 'full_name', 'direction' => 'asc']);
    var_dump($repos[0]->name);
    */

    var_dump($api[0]->name);

} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>