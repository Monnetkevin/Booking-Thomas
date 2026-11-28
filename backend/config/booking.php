<?php

return [

    // Compte administrateur créé par le seeder (DatabaseSeeder), lu depuis .env.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // URL publique de la SPA (liens dans les e-mails).
    'frontend_url' => env('FRONTEND_URL', env('APP_URL')),

    // index.html construit par Vite, servi par Laravel avec les balises de chaque page publique.
    'spa_index' => env('SPA_INDEX_PATH', base_path('../frontend/dist/index.html')),

];
