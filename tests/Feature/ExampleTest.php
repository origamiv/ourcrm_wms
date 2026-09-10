<?php

declare(strict_types=1);
it('redirects guests to login and removes the old token endpoint', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/api/user/1')->assertNotFound();
    $this->getJson('/api/users')->assertUnauthorized();
});
