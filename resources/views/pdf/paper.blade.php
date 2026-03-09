<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 70px 25px 30px 25px;
        }

        @page :first {
            margin-top: 30px;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 14px;
        }

        .school-title {
            text-align: center;
            font-weight: bold;
            font-size: 24px;
            margin-top: 2px;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-table td {
            vertical-align: top;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 25px;
            font-size: 16px;
        }

        .question-row {
            width: 100%;
            margin-top: 8px;
        }

        .marks {
            float: right;
        }

        .options {
            margin-left: 25px;
            margin-top: 5px;
        }

        .line {
            border-bottom: 1px solid #000;
            height: 18px;
        }

        .matching-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .matching-table td {
            padding: 2px 5px;
            vertical-align: top;
        }

        hr {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .subsequent-header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 15px;
            font-weight: bold;
            background-color: white;
            z-index: 10;
        }

        .header-line {
            display: inline-block;
            border-bottom: 1px solid #000;
        }

        .first-page-overlay {
            position: absolute;
            top: -30px;
            left: 0px;
            right: 0px;
            height: 30px;
            background-color: #fff;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <div class="first-page-overlay">&nbsp;</div>

    <header class="subsequent-header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 45%;">Name: <span class="header-line" style="width: 250px;">&nbsp;</span></td>
                <td style="width: 25%;">Sub: {{ $paper->subject }}</td>
                <td style="width: 15%;">Std: {{ $paper->standard }}</td>
                <td style="width: 15%; text-align: right;">Roll No. <span class="header-line"
                        style="width: 50px;">&nbsp;</span></td>
            </tr>
        </table>
    </header>

    <div class="school-title">
        JOYOUS PRIMARY ENGLISH SCHOOL
    </div>

    <table class="header-table">
        <tr>
            <td width="30%">
                <img src="{{ public_path('images/school-logo.png') }}" width="70">
            </td>

            <td width="40%" class="center">
                <strong>{{ $paper->title }}</strong><br>
                SUB: {{ $paper->subject }}<br>
                STD: {{ $paper->standard }}
            </td>

            <td width="30%" class="right">
                (Run by EBAM Trust)<br>
                Fully AC School<br>
                Varachha Road<br>
                Surat - 395006
            </td>
        </tr>
    </table>

    <br>

    <table class="info-table">
        <tr>
            <td><strong>Date:</strong> {{ \Carbon\Carbon::parse($paper->paper_date)->format('d/m/Y') }}</td>
            <td class="right"><strong>Total Marks:</strong> {{ $paper->sections->sum('total_marks') }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px; margin-bottom: 20px;">
        <strong>Name:</strong> _________________________________________________
        <span style="float:right;">
            <strong>Roll No:</strong> __________
        </span>
    </p>

    @php $sectionCount = 1; @endphp

    @foreach ($paper->sections as $section)
        <div class="section-title">
            Q{{ $sectionCount }}. {{ $section->section_name }} - {{ $section->caption }}
            <span class="marks">({{ $section->total_marks }})</span>
        </div>

        @php $questionCount = 1; @endphp

        @foreach ($section->questions as $question)
            @if ($section->section_type != 'drawing')
                <table style="width:100%; margin-top:8px;">
                    <tr>
                        <td style="width:80%;">
                            {{ $questionCount }}) {!! $question->question !!}
                        </td>
                        @if ($section->section_type == 'mcqs' || $section->section_type == 'truefalse')
                            <td style="width:20%; text-align:left;">
                                (&nbsp;&nbsp;&nbsp;&nbsp;)
                            </td>
                        @endif
                    </tr>
                </table>
            @endif

            @if ($section->section_type == 'mcqs')
                <div class="options">
                    @php $options = json_decode($question->options); @endphp
                    @foreach ($options as $opt)
                        ({{ chr(97 + $loop->index) }})
                        {{ $opt->title }}
                        &nbsp;&nbsp;&nbsp;&nbsp;
                    @endforeach
                </div>
            @endif

            @if ($section->section_type == 'question_answer')
                <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                    @for ($i = 0; $i < $question->options; $i++)
                        <tr>
                            <td style="width: 35px; vertical-align: bottom; padding-bottom: 2px;">
                                @if ($i == 0)
                                    <strong>Ans.</strong>
                                @endif
                            </td>
                            <td class="line"></td>
                        </tr>
                    @endfor
                </table>
            @endif

            @if ($section->section_type == 'matching')
                @php
                    $options = json_decode($question->options);
                    $maxWordA = 0;
                    $maxWordB = 0;
                    if (isset($options->matchA) && is_array($options->matchA)) {
                        foreach ($options->matchA as $text) {
                            $maxWordA = max($maxWordA, mb_strlen((string) $text));
                        }
                    }
                    if (isset($options->matchB) && is_array($options->matchB)) {
                        foreach ($options->matchB as $text) {
                            $maxWordB = max($maxWordB, mb_strlen((string) $text));
                        }
                    }
                    // Estimate width in pixels (approx 9px per char for 14px font, plus 40px padding)
                    // Added a limit to prevent columns from overflowing the page
                    $widthA = min(300, max(120, $maxWordA * 9 + 40));
                    $widthB = min(300, max(120, $maxWordB * 9 + 40));
                @endphp
                @if (isset($options->matchA) || isset($options->matchB))
                    <table class="matching-table" style="width: auto; margin-left: 20px;">
                        <tr>
                            <td width="30px"></td>
                            <td width="{{ $widthA }}px" align="center"><strong>A</strong></td>
                            <td width="40px" align="center"><strong>-</strong></td>
                            <td width="{{ $widthB }}px" align="center"><strong>B</strong></td>
                        </tr>
                        @php
                            $matchA = $options->matchA ?? [];
                            $matchB = $options->matchB ?? [];
                            $count = max(count($matchA), count($matchB));
                        @endphp
                        @for ($i = 0; $i < $count; $i++)
                            <tr>
                                <td>{{ $i + 1 }}.</td>
                                <td>{{ $matchA[$i] ?? '' }}</td>
                                <td align="center">-</td>
                                <td>{{ $matchB[$i] ?? '' }}</td>
                            </tr>
                        @endfor
                    </table>
                @endif
            @endif

            @if ($section->section_type == 'drawing')
                @php
                    $options = json_decode($question->options);
                    $images = [];
                    if (is_array($options)) {
                        foreach ($options as $opt) {
                            if (isset($opt->title) && !empty($opt->title)) {
                                $images[] = $opt->title;
                            }
                        }
                    }
                @endphp
                <div style="margin-top: 15px;">
                    @if (count($images) > 0)
                        <table style="width: 100%; border-collapse: collapse;">
                            {{-- Row 1: up to 3 images --}}
                            <tr>
                                @for ($i = 0; $i < 3; $i++)
                                    <td style="text-align: center; width: 33.33%; padding: 10px;">
                                        @if (isset($images[$i]))
                                            <img src="{{ public_path($images[$i]) }}"
                                                style="max-width: 150px; max-height: 150px; display: block; margin: 0 auto;">
                                            <div style="margin-top: 5px;">________________</div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                            {{-- Row 2: remaining images (up to 2) --}}
                            @if (count($images) > 3)
                                <tr>
                                    <td style="width: 33.33%;"></td> {{-- Empty cell for centering --}}
                                    @for ($i = 3; $i < 5; $i++)
                                        <td style="text-align: center; width: 33.33%; padding: 10px;">
                                            @if (isset($images[$i]))
                                                <img src="{{ public_path($images[$i]) }}"
                                                    style="max-width: 150px; max-height: 150px; display: block; margin: 0 auto;">
                                                <div style="margin-top: 5px;">________________</div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endif
                        </table>
                    @else
                        <div
                            style="border: 1px dashed #ccc; height: 180px; width: 400px; margin: 15px auto; padding: 10px; color: #999; text-align: center;">
                            Drawing Area
                        </div>
                        <div style="text-align: center; margin-top: 5px;">________________________________</div>
                    @endif
                </div>
            @endif

            @if ($section->section_type == 'single_image')
                @php $options = json_decode($question->options); @endphp
                <div style="margin-top: 15px; text-align: center;">
                    @if (isset($options[0]->title) && !empty($options[0]->title))
                        <img src="{{ public_path($options[0]->title) }}"
                            style="max-width: 400px; max-height: 400px; display: block; margin: 0 auto;">
                        <div style="margin-top: 10px;">________________________________</div>
                    @else
                        <div
                            style="border: 1px dashed #ccc; height: 250px; width: 450px; margin: 15px auto; padding: 10px; color: #999;">
                            Drawing Area (Single Image)
                        </div>
                        <div style="text-align: center; margin-top: 5px;">________________________________</div>
                    @endif
                </div>
            @endif
            @php $questionCount++; @endphp
        @endforeach

        @php $sectionCount++; @endphp
    @endforeach

</body>

</html>
