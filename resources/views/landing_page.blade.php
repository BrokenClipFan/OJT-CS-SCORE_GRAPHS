<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    @vite(['resources/css/app.css'])
</head>

<body>
    <section class="dropDownSection">
        <h1>CS MONITORING TOOL</h1>
        <h4>Convert your csv file into graphs <span class="highlightSpan">INSTANTLY!<span></h4>
        <div class="dropDownArea">
            <img class="dropDownImage" src="{{ asset('images/icons/document.png') }}" alt="">
            <h4>Drop Your Daily CS Score Here!!!</h4>
            <p>Accepts .csv file</p>
        </div>
    </section>
    <section class="graphsSection">
        <div>
            <h4>Good And Bad Samples</h4>
        </div>
        <div class="sampleGraphContainer">
            <canvas class="sampleGraph"></canvas>
        </div>
    </section>
</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function dropAreaInit() {
        const dropArea = document.querySelector(".dropDownArea");

        const active = () => dropArea.classList.add("dragOver")

        const inactive = () => dropArea.classList.remove("dragOver")

        const prevents = (e) => e.preventDefault();

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            dropArea.addEventListener(eventName, prevents)
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropArea.addEventListener(eventName, active)
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropArea.addEventListener(eventName, inactive)
        });

        dropArea.addEventListener('drop', handleDrop)
    }

    document.addEventListener("DOMContentLoaded", dropAreaInit)
    rows = []

    const handleDrop = async (e) => {
        const dt = e.dataTransfer
        const files = dt.files
        const file = await files[0]
        const rawText = await file.text()
        const unFilteredRows = rawText
            .trim()
            .split(/\r?\n/)
            .filter((line) => line.trim() !== "")
            .map(parseCSVLine);

        unFilteredRows.shift();

        unFilteredRows.filter((row) => row[9] !== "").forEach((row) => {
            const goodSample = (row[4] * row[9]) / 100;
            const badSample = row[4] - goodSample;

            const rowData = {
                provider: row[0],
                aggregate_date: row[2],
                sample_count: row[4],
                technology: row[6],
                consistency: row[9],
                good_sample: goodSample,
                bad_sample: badSample,
            }
            rows.push(rowData)
        })

        calcGoodAndBad()
    }

    function parseCSVLine(line) {
        const result = [];
        let current = "";
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];

            if (char === '"') {
                // Handle escaped quotes ("") inside a quoted field
                if (inQuotes && line[i + 1] === '"') {
                    current += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes; // Toggle inside/outside quotes
                }
            } else if (char === "," && !inQuotes) {
                result.push(current.trim());
                current = "";
            } else {
                current += char;
            }
        }

        result.push(current.trim());
        return result;
    }

    function calcGoodAndBad() {
        const provider = 'globe'.toLowerCase()
        const network = "lte".toLowerCase()

        const groupedByDate = groupByAggregateDateAsArray(rows);
        const dates = groupedByDate.map((el) => el.aggregate_date);

        const samples = groupedByDate.flatMap((el) => el.items.filter((item) => item.provider.toLowerCase() ==
            provider &&
            item.technology.toLowerCase() == network))

        const goodSamples = samples.map((el) => el.good_sample)
        const badSamples = samples.map((el) => el.bad_sample)

        renderSampleGraphs(dates, goodSamples, badSamples)
    }

    function renderSampleGraphs(labels, goodSamples, badSamples) {

        const sampleGraph = document.querySelector(".sampleGraph");

        csvSampleGraph = new Chart(sampleGraph, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Bad Samples',
                        data: badSamples,
                        borderwidth: 1,
                        yAxisID: "y",
                    },
                    {
                        type: 'line',
                        label: 'Good Samples',
                        data: goodSamples,
                        tension: 0.2,
                        yAxisID: "y1",
                    }
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    }

    function groupByAggregateDateAsArray(items) {
        const map = new Map();

        for (const item of items) {
            const key = item.aggregate_date;
            if (!map.has(key)) {
                map.set(key, {
                    aggregate_date: key,
                    items: []
                });
            }
            map.get(key).items.push(item);
        }

        return Array.from(map.values());
    }
</script>

</html>
