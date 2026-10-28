(function ($) {

    "use strict";

    // revenue chart
    if ($("#revenueChart").length) {
        var revenueChartoptions = {
            series: [
                {
                    name: "Earning",
                    data: [
                        467, 559, 313, 782, 599, 567, 399, 399, 796,
                        899, 499,
                    ],
                },
                {
                    name: "Expense",
                    data: [
                        378, 919, 452, 959, 235, 523, 524, 511, 789,
                        349, 789,
                    ],
                },
            ],

            colors: ["#6CD6FD", "#DEF6FF"],
            chart: {
                type: "bar",
                height: 350,
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "55%",
                    endingShape: "rounded",
                },
            },

            legend: {
                itemMargin: {
                    horizontal: 5,
                    vertical: 5,
                },
                horizontalAlign: "center",
                verticalAlign: "center",
                position: "bottom",
                fontSize: "14px",
                fontWight: "bold",
                markers: {
                    radius: 5,
                    height: 14,
                    width: 14,
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                show: true,
                width: 2,
                colors: ["transparent"],
            },
            xaxis: {
                categories: [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "May",
                    "June",
                    "July",
                    "Aug",
                    "Sep",
                    "Oct",
                    "Nov",
                ],
            },
            yaxis: {
                title: {
                    show: false,
                },
                labels: {
                    formatter: function (value) {
                        var val = Math.abs(value);
                        if (val >= 1000) {
                            val = (val / 1000).toFixed(0) + "K";
                        }
                        return val;
                    },
                },
            },
            fill: {
                opacity: 1,
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + "K";
                    },
                },
            },
        };

        var revenueChart = new ApexCharts(
            document.querySelector("#revenueChart"),
            revenueChartoptions
        );
        revenueChart.render();
    }




    let otPieChart1 = {
        series: [623, 314, 239],
        chart: {
            id: "ot-pie-chart-alpha",
            fontFamily: "Lexend, sans-serif",
            type: "donut",
            height: 360,
            toolbar: {
                show: false,
            },
            zoom: {
                enabled: false,
            },
        },

        colors: ["#00F7BF", "#6BCCFD", "#9AFFFF"],
        labels: ["Check In", "Check Out", "Absence"],
        dataLabels: {
            enabled: false,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "65%",
                    labels: {
                        show: true,
                        name: {
                            fontSize: "30px",
                            offsetY: 0,
                            color: "#636E72",
                        },
                        value: {
                            fontSize: "30px",
                            width: 700,
                            offsetY: 20,

                            formatter(val) {
                                return `${val}`;
                            },
                        },
                        total: {
                            show: true,
                            fontSize: "18px",
                            color: "#636E72",
                            label: "Total",

                            formatter: function (w) {
                                return "2400";
                            },
                        },
                    },
                },
            },
        },

        legend: {
            itemMargin: {
                horizontal: 5,
                vertical: 5,
            },
            horizontalAlign: "center",
            verticalAlign: "center",
            position: "top",
            fontSize: "16px",
            fontWight: "bold",
            markers: {
                radius: 3,
                height: 15,
                width: 15,
            },
        },

        responsive: [
            {
                breakpoint: 325,
                options: {
                    legend: {
                        itemMargin: {
                            horizontal: 4,
                            vertical: 0,
                        },
                        horizontalAlign: "center",
                        position: "bottom",
                        fontSize: "14px",
                    },
                },
            },
        ],
    };

    if (document.querySelector("#ot-pie-chart-alpha")) {
        let chart = new ApexCharts(
            document.querySelector("#ot-pie-chart-alpha"),
            otPieChart1
        );
        chart.render();
    }





    // Start revenue 
    var income, expense, revenue;
    var url = $('#url').val();
    $.ajax({
        type: "GET",
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/revenue',
        success: function (data) {
            income = data.income
            expense = data.expense
            revenue = data.revenue
            revenueChart();
        },
        error: function (data) {
            console.log(data);
        }
    });
    function revenueChart() {
        // Calculate totals for pyramid chart
        var totalIncome = income.reduce((a, b) => a + b, 0);
        var totalExpense = expense.reduce((a, b) => a + b, 0);
        var netProfit = totalIncome - totalExpense;

        // Pyramid Chart (using horizontal bar chart with custom styling)
        let pyramidChart = {
            series: [{
                name: 'Amount',
                data: [totalIncome, totalExpense, netProfit]
            }],
            chart: {
                type: 'bar',
                height: 250,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true,
                    barHeight: '70%',
                    dataLabels: {
                        position: 'center'
                    }
                }
            },
            colors: ['#667eea', '#f5576c', '#00f2fe'],
            dataLabels: {
                enabled: true,
                textAnchor: 'middle',
                style: {
                    fontSize: '14px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                formatter: function (val, opt) {
                    var label = opt.w.globals.labels[opt.dataPointIndex];
                    var percentage = ((val / totalIncome) * 100).toFixed(1);
                    return label + ': ' + val.toLocaleString() + ' (' + percentage + '%)';
                },
                offsetX: 0,
                dropShadow: {
                    enabled: false
                }
            },
            xaxis: {
                categories: ['Total Income', 'Total Expense', 'Net Profit'],
                labels: {
                    show: false
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    show: true,
                    style: {
                        fontSize: '13px',
                        fontWeight: 600,
                        colors: ['#667eea', '#f5576c', '#00f2fe']
                    }
                }
            },
            grid: {
                show: false
            },
            legend: {
                show: false
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function (val) {
                        return val.toLocaleString();
                    },
                    title: {
                        formatter: function (seriesName) {
                            return '';
                        }
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.15
                    }
                }
            }
        };

        if ($("#revenue-pyramid-chart").length) {
            const pyramidChartInstance = new ApexCharts(
                document.querySelector("#revenue-pyramid-chart"),
                pyramidChart
            );
            pyramidChartInstance.render();
        }
    }

    // End revenue




    let optionsAnalyticsVisit = {
        series: [35, 25, 45],
        chart: {
            id: "visited_customer_chart",
            type: "donut",
            height: 320,
            toolbar: {
                show: false,
            },
            zoom: {
                enabled: false,
            },
        },
        colors: ["#6CD6FD", "#9597FF", "#00F7BF"],

        labels: ["Desktop Users", "Tablet User", "Mobile User"],

        dataLabels: {
            enabled: false,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "75%",
                    labels: {
                        show: true,
                        name: {
                            fontSize: "12px",
                            offsetY: 0,
                        },
                        value: {
                            fontSize: "12px",
                            offsetY: 0,
                            formatter(val) {
                                return `% ${val}`;
                            },
                        },
                        total: {
                            show: true,
                            fontSize: "16px",
                            label: "Total",

                            formatter: function (w) {
                                return "2400";
                            },
                        },
                    },
                },
            },
        },

        legend: {
            itemMargin: {
                horizontal: 0,
                vertical: 10,
            },
            horizontalAlign: "center",
            verticalAlign: "center",
            position: "right",
            fontFamily: "Lexend",
            fontSize: "15px",
            fontWight: "500",
            markers: {
                radius: 5,
                height: 14,
                width: 14,
            },
        },

        responsive: [
            {
                breakpoint: 1400,
                options: {
                    legend: {
                        itemMargin: {
                            horizontal: 5,
                            vertical: 5,
                        },
                        horizontalAlign: "center",
                        verticalAlign: "center",
                        position: "bottom",
                        fontFamily: "Lexend",
                        fontSize: "15px",
                        fontWight: "500",
                        markers: {
                            radius: 5,
                            height: 14,
                            width: 14,
                        },
                    },
                },
                breakpoint: 420,
                options: {
                    legend: {
                        itemMargin: {
                            horizontal: 5,
                            vertical: 5,
                        },
                        horizontalAlign: "center",
                        verticalAlign: "center",
                        position: "bottom",
                        fontFamily: "Lexend",
                        fontSize: "12px",
                        fontWight: "500",
                        markers: {
                            radius: 5,
                            height: 14,
                            width: 14,
                        },
                    },
                },
            },
        ],
    };

    if ($("#visited_customer_chart").length) {
        if (document.querySelector("#visited_customer_chart")) {
            let chart = new ApexCharts(
                document.querySelector("#visited_customer_chart"),
                optionsAnalyticsVisit
            );
            chart.render();
        }
    }

})(jQuery);
// // group chart label
