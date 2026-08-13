<?php

/*
| Only the messages this app words differently. Everything else falls through to
| the framework's own `lang/en/validation.php`, which is merged in underneath.
*/
return [
    'custom' => [
        'username' => [
            'unique' => 'Username already taken',
        ],
    ],
];
