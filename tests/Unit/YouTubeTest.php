<?php

use App\Support\YouTube;

it('reads the video id from every common link shape', function (string $input) {
    expect(YouTube::id($input))->toBe('dQw4w9WgXcQ');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'http://youtube.com/watch?feature=share&v=dQw4w9WgXcQ',
    'https://m.youtube.com/watch?v=dQw4w9WgXcQ&t=10s',
    'www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ?si=abc',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'https://www.youtube.com/live/dQw4w9WgXcQ',
    '  dQw4w9WgXcQ  ',
]);

it('returns null for anything else', function (?string $input) {
    expect(YouTube::id($input))->toBeNull();
})->with([null, '', 'hello', 'https://vimeo.com/76979871', 'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube.com/watch?v=short', 'https://www.youtube.com/watch?v[]=dQw4w9WgXcQ', 'https://www.youtube.com/playlist?list=PL1234567890', 'javascript:alert(1)']);

it('builds the addresses the site uses', function () {
    expect(YouTube::watchUrl('dQw4w9WgXcQ'))->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->and(YouTube::embedUrl('dQw4w9WgXcQ'))->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0')
        ->and(YouTube::embedUrl('dQw4w9WgXcQ', true))->toContain('autoplay=1')
        ->and(YouTube::thumbnail('dQw4w9WgXcQ'))->toBe('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});
