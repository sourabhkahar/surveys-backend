<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 40px 40px 40px 40px;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 14px;
        }

        .school-title {
            text-align: center;
            font-weight: bold;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
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
            margin-top: 20px;
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

        hr {
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

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

    <hr>

    <table class="info-table">
        <tr>
            <td><strong>Date:</strong> {{ \Carbon\Carbon::parse($paper->paper_date)->format('d/m/Y') }}</td>
            <td class="right"><strong>Total Marks:</strong> {{ $paper->sections->sum('total_marks') }}</td>
        </tr>
    </table>

    <p>
        <strong>Name:</strong> ________________________________
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
            <table style="width:100%; margin-top:8px;">
                <tr>
                    <td style="width:80%;">
                        {{ $questionCount }}) {!! $question->question !!}
                    </td>
                    @if ($section->section_type == 'mcqs')
                        <td style="width:20%; text-align:left;">
                            (&nbsp;&nbsp;&nbsp;&nbsp;)
                        </td>
                    @endif
                </tr>
            </table>

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
                @for ($i = 0; $i < $question->options; $i++)
                    <div class="line"></div>
                @endfor
            @endif

            @php $questionCount++; @endphp
        @endforeach

        @php $sectionCount++; @endphp
    @endforeach

</body>

</html>
