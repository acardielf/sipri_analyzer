<?php

namespace App\Service;

use App\Entity\Curso;
use App\Entity\Provincia;
use DateTime;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartService
{

    /**
     * @param ChartBuilderInterface $chartBuilder
     * @param array<Curso> $cursos
     * @param array $result
     * @return Chart
     */
    public function createChartPlazasPorCursosGeneral(
        ChartBuilderInterface $chartBuilder,
        array $cursos,
        array $result,
        array $index_weeks,
        ?int $currentWeek = null,
    ): Chart {
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => $this->getWeekLabels($result),
            'datasets' => $this->buildDataSetPlazasPorCurso($cursos, $result, $index_weeks),
        ]);

        $chart->setOptions($this->getDefaultOptions($currentWeek));

        return $chart;
    }

    /**
     * @param ChartBuilderInterface $chartBuilder
     * @param array<Curso> $cursos
     * @param array<Provincia> $provincias
     * @param array $result
     * @return Chart
     */
    public function createChartByEspecialidadPorProvincia(
        ChartBuilderInterface $chartBuilder,
        array $cursos,
        array $provincias,
        array $result
    ): Chart {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $this->getCurseNameLabels($cursos),
            'datasets' => $this->buildDataSetEspecialidadPorProvincia($cursos, $provincias, $result),
        ]);

        $chart->setOptions($this->getDefaultOptions());

        return $chart;
    }

    /**+
     * @param array<Curso> $cursos
     * @return array
     */
    private function getCurseNameLabels(array $cursos): array
    {
        $labels = [];
        foreach ($cursos as $curso) {
            $labels[] = sprintf('%s', $curso->getNombre());
        }
        return $labels;
    }

    /**
     * @param array<Curso> $cursos
     * @param array<Provincia> $provincias
     * @param array $result
     * @return array
     */
    private function buildDataSetEspecialidadPorProvincia(array $cursos, array $provincias, array $result): array
    {
        $data = [];
        $colors = $this->getColors();

        $transpose = [];
        $totals = [];
        foreach ($cursos as $curso) {
            $totals[$curso->getId()] = 0;
        }
        foreach ($provincias as $provincia) {
            foreach ($cursos as $curso) {
                $value = $result[$curso->getId()][$provincia->getId()]['plazas'] ?? 0;
                $transpose[$provincia->getId()][$curso->getId()] = $value;
                $totals[$curso->getId()] += $value;
            }
        }

        $data[] = [
            'label' => 'Total Andalucía',
            'data' => array_values($totals),
            'borderWidth' => 3,
            'backgroundColor' => 'var(--sipri-blue)',
            'borderColor' => '#1a5f28',
            'hidden' => false,
            'datalabels' => [
                'display' => true,
                'anchor' => 'end',
                'align' => 'top',
                'color' => '#1a5f28',
                'font' => ['weight' => 'bold', 'size' => 11],
                'padding' => 4,
            ],
        ];

        $i = 0;
        foreach ($provincias as $provincia) {
            $data[] = [
                'label' => $provincia->getNombre(),
                'data' => array_values($transpose[$provincia->getId()] ?? []),
                'borderWidth' => 2,
                'backgroundColor' => $colors[$i],
                'borderColor' => $colors[$i],
                'hidden' => true,
                'datalabels' => [
                    'display' => true,
                    'anchor' => 'end',
                    'align' => 'top',
                    'color' => $colors[$i],
                    'font' => ['weight' => 'bold', 'size' => 10],
                    'padding' => 3,
                ],
            ];
            $i++;
        }

        return $data;
    }

    /**
     * @param array<Curso $listCursos
     * @param array $result
     * @return array
     */
    private function buildDataSetPlazasPorCurso(array $listCursos, array $result, array $index_weeks): array
    {
        $datasets = [];

        $colors = $this->getColors();
        $transpose = [];

        foreach ($listCursos as $curso) {
            foreach ($index_weeks as $week) {
                $transpose[$curso->getId()][$week] = [];
            }
        }

        foreach ($result as $week => $cursos) {
            foreach ($cursos as $curso => $convocatorias) {
                $transpose[$curso][$week] = $convocatorias;
            }
        }

        //obtenemos la media de plazas por semana
        $averagePlazasByWeek = [];

        foreach ($result as $week => $cursos) {
            $totalPlazas = 0;
            $countConvocatorias = 0;

            foreach ($cursos as $curso => $convocatorias) {
                foreach ($convocatorias as $convocatoria) {
                    $totalPlazas += $convocatoria['plazas'] ?? 0;
                    $countConvocatorias++;
                }
            }

            if ($countConvocatorias > 0) {
                $averagePlazasByWeek[] = [
                    'x' => $week,
                    'y' => (int)($totalPlazas / count($listCursos)),
                ];
            } else {
                $averagePlazasByWeek[] = [
                    'x' => $week,
                    'y' => 0,
                ];
            }
        }

        $i = 0;
        $datasets[] = [
            'id' => 9999, // ID ficticio para ordenar descendentemente
            'label' => 'Promedio semanal',
            'type' => 'line',
            'data' => $averagePlazasByWeek,
            'backgroundColor' => "#000",
            'borderColor' => "#000",
            'fill' => false,
        ];


        foreach ($transpose as $curso => $semana) {
            $data = [];
            foreach ($semana as $week => $convocatorias) {
                $plazas = 0;
                foreach ($convocatorias as $convocatoria) {
                    $plazas += $convocatoria['plazas'] ?? 0;
                }

                $data[] = [
                    'x' => (int)$week,
                    'y' => (int)$plazas,
                ];
            }

            /**
             * @var Curso $selectedCurso
             */
            $selectedCurso = array_values(array_filter($listCursos, function (Curso $iterableCurso) use ($curso) {
                return $iterableCurso->getId() == $curso;
            }))[0];


            usort($listCursos, function (Curso $a, Curso $b) {
                return $b->getId() <=> $a->getId();
            });
            $lastTwoCurses = array_slice($listCursos, 0, 2);
            $lastTwoCursesIds = array_map(function (Curso $curso) {
                return $curso->getId();
            }, $lastTwoCurses);


            $datasets[] = [
                'id' => $selectedCurso->getId(),
                'label' => sprintf('Curso %s', $selectedCurso->getNombre()),
                'data' => $data,
                'backgroundColor' => $colors[$i],
                'borderColor' => $colors[$i],
                'fill' => false,
                'hidden' => !in_array($selectedCurso->getId(), $lastTwoCursesIds),
            ];
            $i++;
        }

        // order descent by curse
        usort($datasets, function ($a, $b) {
            return (int)$b['id'] <=> (int)$a['id'];
        });
        return $datasets;
    }


    private function getWeekLabels(array $results): array
    {
        $labels = [];
        foreach ($results as $week => $cursos) {
            $labels[] = $week;
        }
        return $labels;
    }


    private function getColors(): array
    {
        return [
            '#CB4335',
            '#1F618D',
            '#F1C40F',
            '#27AE60',
            '#884EA0',
            '#D35400',
            '#F39C12',
            '#16A085',
        ];
    }

    private function getDefaultOptions(?int $currentWeek = null): array
    {
        $options = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'autocolors' => [
                    'enabled' => true,
                    'mode' => 'dataset',
                ],
            ],
        ];

        if ($currentWeek !== null) {
            $options['plugins']['annotation'] = [
                'annotations' => [
                    [
                        'type'            => 'box',
                        'xScaleID'        => 'x',
                        'xMin'            => $currentWeek,
                        'xMax'            => $currentWeek,
                        'backgroundColor' => 'rgba(196, 17, 17, 0.10)',
                        'borderColor'     => 'rgba(196, 17, 17, 0.70)',
                        'borderWidth'     => 2,
                        'label'           => [
                            'enabled'   => true,
                            'content'   => 'Semana actual',
                            'position'  => 'start',
                            'color'     => '#ffffff',
                            'backgroundColor' => 'rgba(196, 17, 17, 0.80)',
                            'font'      => ['size' => 11],
                            'padding'   => 4,
                        ],
                    ],
                ],
            ];
        }

        return $options;
    }


}
