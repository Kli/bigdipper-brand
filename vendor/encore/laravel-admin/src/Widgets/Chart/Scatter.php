<?php

namespace Encore\Admin\Widgets\Chart;

class Scatter extends Chart
{
    protected $labels = [];
    protected $options = [
                            'scales' => [
                                'yAxes' => [[
                                    'ticks' => [
                                        'beginAtZero' =>true
                                    ]
                                ]]
                            ],
                            'scaleShowGridLines' => true,
                            'scaleGridLineColor' => "rgba(0,0,0,.05)",
                            'scaleGridLineWidth' => 1,
                            'scaleShowHorizontalLines' => true,
                            'scaleShowVerticalLines' => true,
                            'barShowStroke' => true,
                            'barStrokeWidth' => 1,
                            'barValueSpacing' => 5,
                            'barDatasetSpacing' => 1,
                            'responsive' => true,
                            'maintainAspectRatio' => true,
                            'datasetFill' => false
                        ];

    public function __construct($labels = [], $data = [])
    {
        $this->data['datasets']['labels'] = $labels;

        $this->data['datasets'] = $data;

    }

    public function options($options = [])
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function script()
    {
        $data = json_encode($this->data);
        
        $options = json_encode($this->options);

        return <<<EOT

(function() {
    var plugin = {
        afterDatasetsDraw: function(chart, easing) {
            // To only draw at the end of animation, check for easing === 1
            var ctx = chart.ctx;

            chart.data.datasets.forEach(function (dataset, i) {
                console.log(dataset);
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function(element, index) {
                        // Draw the text in black, with the specified font
                        ctx.fillStyle = '#818181';

                        var fontSize = 12;
                        var fontStyle = 'normal';
                        var fontFamily = 'Helvetica Neue';
                        ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);

                        // Just naively convert to string for now
                        var dataString = dataset.labels[index].toString();

                        // Make sure alignment settings are correct
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        var padding = 5;
                        var position = element.tooltipPosition();
                        ctx.fillText(dataString, position.x, position.y - (fontSize / 2) - padding);
                    });
                }
            });
        }
    };

    var canvas = $("#{$this->elementId}").get(0).getContext("2d");
    var chart = new Chart(canvas, {type:"scatter",plugins: [plugin],data:$data, options:$options});

})();
EOT;
    }
}
