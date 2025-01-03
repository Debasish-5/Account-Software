google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Batch', 'Total', 'Recieved', 'Remaining'],
          ['BBA', 680000, 520000, 160000],
          ['BCA', 820000, 460000, 360000],
          ['B.Sc CS(H)', 820000, 660000, 160000]
        ]);

        var options = {
          chart: {
            title: 'Batch Wise Fee Collection',
            subtitle: 'BBA, BCA, B.Sc CS(H)',
          },
          bars: 'horizontal', // Required for Material Bar Charts.
          colors: ['#8E44AD','#2ECC71','#3498DB']
        };

        var chart = new google.charts.Bar(document.getElementById('barchart_material'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }