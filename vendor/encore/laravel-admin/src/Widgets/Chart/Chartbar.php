<?php

namespace Encore\Admin\Widgets\Chart;

use Illuminate\Support\Arr;

class Chartbar extends Chart
{
    protected $labels = [];
    protected $type = 'bar';
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
        $this->data['labels'] = $labels;

        $this->data['datasets'] = [];

        $this->add($data);
    }

    public function add($label, $data = [], $fillColor = '')
    {
        $this->data['datasets'] = $label;
        
        return $this;
    }

    public function options($options = [])
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function type($type)
    {
        $this->type = $type;
    }

    protected $defaultColors = [
        '#dd4b39', '#00a65a', '#f39c12',
        '#00c0ef', '#3c8dbc', '#0073b7',
        '#39cccc', '#ff851b', '#01ff70',
        '#605ca8', '#f012be', '#777',
        '#001f3f', '#d2d6de',
    ];

    protected function fillColor($data)
    {
        foreach ($data['datasets'] as &$item) {
            if (empty($item['fillColor'])) {
                $item['fillColor'] = array_shift($this->defaultColors);
            }
        }

        return $data;
    }

    public function script()
    {
        $data = $this->fillColor($this->data);

        $data = json_encode($data);

        $options = json_encode($this->options);

        return <<<EOT

(function() {
    var canvas = $("#{$this->elementId}").get(0).getContext("2d");
    // var chart = new Chart(canvas).Bar($data, $options);
    var chart = new Chart(canvas, {type:"$this->type",data:$data, options:$options});

})();
EOT;
    }
}
