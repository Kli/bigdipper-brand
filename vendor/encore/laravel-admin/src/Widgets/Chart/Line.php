<?php

namespace Encore\Admin\Widgets\Chart;

class Line extends Chart
{
    protected $datasets = [];
    protected $options = [];

    public function __construct($labels = [], $data = [])
    {
        $this->data['labels'] = $labels;
        
        $this->add($data);
    }

    public function add($data)
    {
        $this->data['datasets'] = $data;
        
        return $this;
    }



    public function script()
    {
        $options = json_encode($this->options);
        $data = json_encode($this->data);
        
        return <<<EOT

(function(){
    var data = $data;
    var canvas = $("#{$this->elementId}").get(0).getContext("2d");
    var chart = new Chart(canvas, {type:"line",data:data, options:$options});
    // var chart = new Chart(canvas).Line(data, $options);
})();
EOT;
    }
}
