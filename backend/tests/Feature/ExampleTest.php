<?php

it('répond sur la route de santé', function () {
    $this->get('/up')->assertOk();
});
