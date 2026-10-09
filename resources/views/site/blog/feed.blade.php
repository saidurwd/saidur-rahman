<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ url('/blog') }}</link>
        <description>{{ $description }}</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        <lastBuildDate>{{ $updated->toRssString() }}</lastBuildDate>
        <atom:link href="{{ url('/feed') }}" rel="self" type="application/rss+xml"/>
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ url($post->publicPath()) }}</link>
                <guid isPermaLink="true">{{ url($post->publicPath()) }}</guid>
                <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
                @if ($post->category)<category>{{ $post->category->name }}</category>@endif
                @if ($post->excerpt)<description>{{ $post->excerpt }}</description>@endif
                @if ($post->body)<content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $post->body) !!}]]></content:encoded>@endif
            </item>
        @endforeach
    </channel>
</rss>
