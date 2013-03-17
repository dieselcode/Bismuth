<?php

include '../vendor/autoload.php';

try {
    $github = new \Bismuth\Endpoint\GitHub(
        new \Bismuth\Core\Auth\Basic('<username>', '<password>')
    );

    // update our repo with the patch data
    $api = $github
            ->user()                    // user object
            ->current()                 // current user (logged in)
            ->repos()                   // repository object
            ->list(array(               // repository list (with options)
                'sort' => 'full_name',
                'direction' => 'asc'
            ));

    foreach ($api as $k => $v) {
        echo $v->full_name . ' - (Created: ' . $v->created_at . ')' . PHP_EOL;
    }

} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>