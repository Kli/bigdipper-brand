<div {!! $attributes !!}>
    <ul class="nav nav-tabs">
        <li class="header"><i class="fa fa-{{ $icon }}"></i>&nbsp;{{ $title }}</li>
        @foreach($tabs as $id => $tab)
        <li class="pull-right {{ $id == $active ? 'active' : '' }}" ><a href="#tab_{{ $tab['id'] }}" data-toggle="tab">{{ $tab['title'] }}</a></li>
        @endforeach

        @if (!empty($dropDown))
        <li class="pull-right dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                Dropdown <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                @foreach($dropDown as $link)
                <li role="presentation"><a role="menuitem" tabindex="-1" href="{{ $link['href'] }}">{{ $link['name'] }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>
    <div class="tab-content">
        @foreach($tabs as $id => $tab)
        <div class="tab-pane {{ $id == $active ? 'active' : '' }}" id="tab_{{ $tab['id'] }}">
            {!! $tab['content'] !!}
        </div>
        @endforeach

    </div>
</div>