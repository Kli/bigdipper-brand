<?php

namespace Encore\Admin\Widgets\Chart;

class Doughnut extends Chart
{
    protected $labels = [];
    protected $options = [
                            
                        ];

    public function __construct($labels = [], $datasets = [])
    {
        $this->data['labels'] = $labels;

        $this->data['datasets'] = $datasets;

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
    console.log($options);
    var canvas = $("#{$this->elementId}").get(0).getContext("2d");
    var chart = new Chart(canvas, {type:"doughnut",data:$data, options:$options});

})();
EOT;
    }
}
