<?php


test('health endpoint responds', function () {
    $this->get('/up')->assertStatus(200);
    $this->getJson('/api/auth/up')->assertStatus(200)->assertJson(['status' => 'ok']);
});
