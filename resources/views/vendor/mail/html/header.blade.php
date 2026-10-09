@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; letter-spacing: 0.01em; color: #0f1b2d;">
{!! $slot !!}
</a>
</td>
</tr>
