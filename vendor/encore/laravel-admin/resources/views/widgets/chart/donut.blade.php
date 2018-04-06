<div class="row">
    <div class="col-md-12">
        <div class="chart-responsive" style="position: relative; height: 300px;">
            <div id="donutPie"></div>
        </div><!-- ./chart-responsive -->
        <div>
            @foreach($data as $item)
            <i class="fa fa-square" style="color: {{ $item['color'] }} !important;"></i> {{ $item['label'] }}
            @endforeach
        </div>
    </div><!-- /.col -->
</div>