<?php

namespace Encore\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class InfoBox extends Widget implements Renderable
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.info-box';

    /**
     * @var array
     */
    protected $datas = [];

    /**
     * InfoBox constructor.
     *
     * @param string $name
     * @param string $icon
     * @param string $color
     * @param string $link
     * @param string $info
     */
    public function __construct($name, $icon, $color, $link, $info, $linkWords='')
    {
        $this->datas = [
            'name'  => $name,
            'icon'  => $icon,
            'link'  => $link,
            'info'  => $info,
            'linkWords'=> $linkWords,
        ];

        $this->class("small-box bg-$color");
    }

    /**
     * @return string
     */
    public function render()
    {
        $variables = array_merge($this->datas, ['attributes' => $this->formatAttributes()]);

        return view($this->view, $variables)->render();
    }
}
