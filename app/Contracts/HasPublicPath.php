<?php

namespace App\Contracts;

/** A record that has a page on the public site, so slug changes can redirect and admin can link to it. */
interface HasPublicPath
{
    public function publicPath(?string $slug = null): string;
}
