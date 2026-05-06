<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" style="text-align: center; font-size: 12px; color: #888888; padding: 20px;">

    {{-- Custom Footer Content --}}
    <p style="margin: 0;">
        © {{ date('Y') }} <strong>Ariatyx Gaming</strong>. All rights reserved.
    </p>

    <p style="margin: 5px 0 0 0;">
        Powering immersive gaming experiences.
    </p>

    {{-- Default Laravel Slot (optional, keep if needed) --}}
    {{ Illuminate\Mail\Markdown::parse($slot) }}

</td>
</tr>
</table>
</td>
</tr>