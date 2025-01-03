google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Months', '2022', '2023', '2024'],
          ['Jan', 1000, 400, 200],
          ['Feb', 1170, 460, 250],
          ['Mar', 660, 1120, 300],
          ['Apr', 1030, 540, 350],
          ['May', 1000, 400, 200],
          ['Jun', 1170, 460, 250],
          ['Jul', 660, 1120, 300],
          ['Aug', 1030, 540, 350],
          ['Sep', 1000, 400, 200],
          ['Oct', 1170, 460, 250],
          ['Nov', 660, 1120, 300],
          ['Dec', 1030, 540, 350]
        ]);

        var options = {
            chart: {
              title: 'Year Wise Fee Collections',
              subtitle: 'Year 2022, 2023 and 2024',
            },
            colors: ['#8E44AD','#2ECC71','#3498DB']
          };

        var chart = new google.charts.Bar(document.getElementById('columnchart_material'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }