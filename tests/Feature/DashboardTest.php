<?php

test('dashboard route is not available', function () {
    $response = $this->get('/dashboard');

    $response->assertNotFound();
});
