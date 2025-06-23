<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use League\Csv\Reader;

class BullyingDataController extends Controller
{
    /**
     * Obtener los KPIs de bullying para el dashboard
     */
    public function getKPIs()
    {
        try {
            // Procesar datos de incidentes de bullying
            $incidentsData = $this->processIncidentsData();
            
            // Procesar datos de cyberbullying (opcional)
            $cyberbullyingData = [];
            try {
                $cyberbullyingData = $this->processCyberbullyingData();
            } catch (\Exception $e) {
                 Log::warning('No se pudo cargar datos de cyberbullying: ' . $e->getMessage());
             }
            
            // Procesar datos de tweets (opcional)
            $tweetsData = [];
            try {
                $tweetsData = $this->processTweetsData();
            } catch (\Exception $e) {
                 Log::warning('No se pudo cargar datos de tweets: ' . $e->getMessage());
             }
            
            $kpis = [
                'total_incidents' => count($incidentsData),
                'total_cyberbullying_cases' => count($cyberbullyingData),
                'total_tweets_analyzed' => count($tweetsData),
                'high_severity_incidents' => $this->getHighSeverityCount($incidentsData),
                'resolved_incidents' => $this->getResolvedCount($incidentsData),
                'average_resolution_time' => $this->getAverageResolutionTime($incidentsData),
                'cyberbullying_detection_rate' => $this->getCyberbullyingDetectionRate($tweetsData),
                'most_common_location' => $this->getMostCommonLocation($incidentsData),
                'most_affected_age_group' => $this->getMostAffectedAgeGroup($incidentsData),
                'incident_trend' => $this->getIncidentTrend($incidentsData)
            ];
            
            return response()->json($kpis);
        } catch (\Exception $e) {
            Log::error('Error en getKPIs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al procesar datos',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * Obtener todos los incidentes de bullying
     */
    public function getIncidents()
    {
        try {
            $incidents = $this->processIncidentsData();
            
            return response()->json([
                'total' => count($incidents),
                'incidents' => $incidents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar incidentes',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener datos para gráficos específicos
     */
    public function getChartData($type)
    {
        try {
            $incidents = $this->processIncidentsData();
            
            // Cargar datos opcionales con manejo de errores
            $cyberbullying = [];
            try {
                $cyberbullying = $this->processCyberbullyingData();
            } catch (\Exception $e) {
                 Log::warning('No se pudo cargar datos de cyberbullying para gráfico: ' . $e->getMessage());
             }
            
            $tweets = [];
            try {
                $tweets = $this->processTweetsData();
            } catch (\Exception $e) {
                 Log::warning('No se pudo cargar datos de tweets para gráfico: ' . $e->getMessage());
             }
            
            switch ($type) {
                case 'monthly':
                    return $this->getMonthlyData($incidents);
                case 'types':
                    return $this->getTypeDistribution($incidents);
                case 'severity':
                    return $this->getSeverityData($incidents);
                case 'locations':
                    return $this->getLocationData($incidents);
                case 'age-groups':
                    return $this->getAgeGroupData($incidents);
                case 'status':
                    return $this->getStatusData($incidents);
                case 'cyberbullying-sentiment':
                    return $this->getCyberbullingSentimentData($cyberbullying);
                case 'tweets-classification':
                    return $this->getTweetsClassificationData($tweets);
                case 'resolution-time':
                    return $this->getResolutionTimeData($incidents);
                case 'reporting-source':
                    return $this->getReportingSourceData($incidents);
                default:
                    return response()->json(['error' => 'Tipo de gráfico no válido'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error en getChartData para tipo ' . $type . ': ' . $e->getMessage());
             Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al generar gráfico',
                'message' => $e->getMessage(),
                'type' => $type,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * Datos mensuales para gráfico de líneas
     */
    private function getMonthlyData($incidents)
    {
        $monthlyData = [];
        
        foreach ($incidents as $incident) {
            $month = Carbon::parse($incident['date'])->format('M Y');
            $monthlyData[$month] = ($monthlyData[$month] ?? 0) + 1;
        }
        
        // Ordenar por fecha
        ksort($monthlyData);
        
        return [
            'labels' => array_keys($monthlyData),
            'data' => array_values($monthlyData)
        ];
    }
    
    /**
     * Distribución por tipos para gráfico de barras
     */
    private function getTypeDistribution($incidents)
    {
        $typeData = [];
        
        foreach ($incidents as $incident) {
            $type = $incident['type'];
            $typeData[$type] = ($typeData[$type] ?? 0) + 1;
        }
        
        return [
            'labels' => array_keys($typeData),
            'data' => array_values($typeData),
            'colors' => [
                '#dc3545', // Rojo para Physical
                '#fd7e14', // Naranja para Verbal
                '#6f42c1', // Púrpura para Cyberbullying
                '#20c997'  // Verde para Social Exclusion
            ]
        ];
    }
    
    /**
     * Datos de severidad
     */
    private function getSeverityData($incidents)
    {
        $severityData = [];
        
        foreach ($incidents as $incident) {
            $severity = 'Nivel ' . $incident['severity'];
            $severityData[$severity] = ($severityData[$severity] ?? 0) + 1;
        }
        
        return [
            'labels' => array_keys($severityData),
            'data' => array_values($severityData)
        ];
    }
    
    /**
     * Datos por ubicación
     */
    private function getLocationData($incidents)
    {
        $locationData = [];
        
        foreach ($incidents as $incident) {
            $location = $incident['location'];
            $locationData[$location] = ($locationData[$location] ?? 0) + 1;
        }
        
        // Ordenar por cantidad descendente
        arsort($locationData);
        
        return [
            'labels' => array_keys($locationData),
            'data' => array_values($locationData)
        ];
    }
    
    /**
     * Procesar datos de incidentes de bullying desde CSV
     */
    private function processIncidentsData()
    {
        $csvPath = public_path('data/bullying_incidents.csv');
        
        if (!File::exists($csvPath)) {
            throw new \Exception('Archivo de incidentes no encontrado');
        }
        
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        
        return iterator_to_array($csv->getRecords());
    }
    
    /**
     * Procesar datos de detección de cyberbullying desde CSV
     */
    private function processCyberbullyingData()
    {
        $csvPath = public_path('data/cyberbullying_detection_dataset.csv');
        
        if (!File::exists($csvPath)) {
            throw new \Exception('Archivo de cyberbullying no encontrado');
        }
        
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        
        return iterator_to_array($csv->getRecords());
    }
    
    /**
     * Procesar datos de tweets de cyberbullying desde CSV
     */
    private function processTweetsData()
    {
        $csvPath = public_path('data/cyberbullying_tweets.csv');
        
        if (!File::exists($csvPath)) {
            throw new \Exception('Archivo de tweets no encontrado');
        }
        
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        
        return iterator_to_array($csv->getRecords());
    }
    
    /**
     * Obtener conteo de incidentes de alta severidad
     */
    private function getHighSeverityCount($incidents)
    {
        return count(array_filter($incidents, function($incident) {
            return isset($incident['severity']) && (int)$incident['severity'] >= 4;
        }));
    }
    
    /**
     * Obtener conteo de incidentes resueltos
     */
    private function getResolvedCount($incidents)
    {
        return count(array_filter($incidents, function($incident) {
            return isset($incident['status']) && $incident['status'] === 'Resolved';
        }));
    }
    
    /**
     * Obtener tiempo promedio de resolución
     */
    private function getAverageResolutionTime($incidents)
    {
        $resolvedIncidents = array_filter($incidents, function($incident) {
            return isset($incident['status']) && $incident['status'] === 'Resolved' && 
                   isset($incident['resolved_days']) && !empty($incident['resolved_days']);
        });
        
        if (empty($resolvedIncidents)) {
            return 0;
        }
        
        $totalDays = array_sum(array_column($resolvedIncidents, 'resolved_days'));
        return round($totalDays / count($resolvedIncidents), 1);
    }
    
    /**
     * Obtener tasa de detección de cyberbullying
     */
    private function getCyberbullyingDetectionRate($tweets)
    {
        if (empty($tweets)) {
            return 0;
        }
        
        $cyberbullyingTweets = array_filter($tweets, function($tweet) {
            return isset($tweet['cyberbullying_type']) && $tweet['cyberbullying_type'] !== 'not_cyberbullying';
        });
        
        return round((count($cyberbullyingTweets) / count($tweets)) * 100, 1);
    }
    
    /**
     * Obtener ubicación más común
     */
    private function getMostCommonLocation($incidents)
    {
        $locations = array_column($incidents, 'location');
        $locationCounts = array_count_values($locations);
        arsort($locationCounts);
        
        return array_key_first($locationCounts) ?? 'N/A';
    }
    
    /**
     * Obtener grupo de edad más afectado
     */
    private function getMostAffectedAgeGroup($incidents)
    {
        $ageGroups = array_column($incidents, 'age_group');
        $ageGroupCounts = array_count_values($ageGroups);
        arsort($ageGroupCounts);
        
        return array_key_first($ageGroupCounts) ?? 'N/A';
    }
    
    /**
     * Obtener tendencia de incidentes
     */
    private function getIncidentTrend($incidents)
    {
        $monthlyData = [];
        
        foreach ($incidents as $incident) {
            if (isset($incident['date'])) {
                $month = Carbon::parse($incident['date'])->format('Y-m');
                $monthlyData[$month] = ($monthlyData[$month] ?? 0) + 1;
            }
        }
        
        ksort($monthlyData);
        $values = array_values($monthlyData);
        
        if (count($values) < 2) {
            return 'stable';
        }
        
        $lastMonth = end($values);
        $previousMonth = prev($values);
        
        if ($lastMonth > $previousMonth) {
            return 'increasing';
        } elseif ($lastMonth < $previousMonth) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Datos por grupo de edad
     */
    private function getAgeGroupData($incidents)
    {
        $ageGroupData = [];
        
        foreach ($incidents as $incident) {
            if (isset($incident['age_group'])) {
                $ageGroup = $incident['age_group'];
                $ageGroupData[$ageGroup] = ($ageGroupData[$ageGroup] ?? 0) + 1;
            }
        }
        
        return [
            'labels' => array_keys($ageGroupData),
            'data' => array_values($ageGroupData),
            'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
        ];
    }
    
    /**
     * Datos por estado
     */
    private function getStatusData($incidents)
    {
        $statusData = [];
        
        foreach ($incidents as $incident) {
            if (isset($incident['status'])) {
                $status = $incident['status'];
                $statusData[$status] = ($statusData[$status] ?? 0) + 1;
            }
        }
        
        return [
            'labels' => array_keys($statusData),
            'data' => array_values($statusData),
            'colors' => ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
        ];
    }
    
    /**
     * Datos de sentimiento de cyberbullying
     */
    private function getCyberbullingSentimentData($cyberbullying)
    {
        $sentimentData = [];
        
        foreach ($cyberbullying as $case) {
            if (isset($case['Sentiment Score'])) {
                $score = (float)$case['Sentiment Score'];
                if ($score >= 0.1) {
                    $sentiment = 'Positive';
                } elseif ($score <= -0.1) {
                    $sentiment = 'Negative';
                } else {
                    $sentiment = 'Neutral';
                }
                $sentimentData[$sentiment] = ($sentimentData[$sentiment] ?? 0) + 1;
            }
        }
        
        return [
            'labels' => array_keys($sentimentData),
            'data' => array_values($sentimentData),
            'colors' => ['#dc3545', '#6c757d', '#28a745']
        ];
    }
    
    /**
     * Datos de clasificación de tweets
     */
    private function getTweetsClassificationData($tweets)
    {
        $classificationData = [];
        
        foreach ($tweets as $tweet) {
            if (isset($tweet['cyberbullying_type'])) {
                $type = $tweet['cyberbullying_type'];
                $classificationData[$type] = ($classificationData[$type] ?? 0) + 1;
            }
        }
        
        return [
            'labels' => array_keys($classificationData),
            'data' => array_values($classificationData),
            'colors' => ['#28a745', '#dc3545']
        ];
    }
    
    /**
     * Datos de tiempo de resolución
     */
    private function getResolutionTimeData($incidents)
    {
        $timeRanges = [
            '1-5 días' => 0,
            '6-10 días' => 0,
            '11-20 días' => 0,
            '21-30 días' => 0,
            'Más de 30 días' => 0
        ];
        
        foreach ($incidents as $incident) {
            if (isset($incident['resolved_days']) && !empty($incident['resolved_days'])) {
                $days = (int)$incident['resolved_days'];
                if ($days <= 5) {
                    $timeRanges['1-5 días']++;
                } elseif ($days <= 10) {
                    $timeRanges['6-10 días']++;
                } elseif ($days <= 20) {
                    $timeRanges['11-20 días']++;
                } elseif ($days <= 30) {
                    $timeRanges['21-30 días']++;
                } else {
                    $timeRanges['Más de 30 días']++;
                }
            }
        }
        
        return [
            'labels' => array_keys($timeRanges),
            'data' => array_values($timeRanges),
            'colors' => ['#28a745', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1']
        ];
    }
    
    /**
     * Datos por fuente de reporte
     */
    private function getReportingSourceData($incidents)
    {
        $sourceData = [];
        
        foreach ($incidents as $incident) {
            if (isset($incident['reported_by'])) {
                $source = $incident['reported_by'];
                $sourceData[$source] = ($sourceData[$source] ?? 0) + 1;
            }
        }
        
        return [
            'labels' => array_keys($sourceData),
            'data' => array_values($sourceData),
            'colors' => ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']
        ];
    }
    
    /**
     * Regenerar datos de bullying
     */
    public function regenerateData()
    {
        try {
            // Verificar que los archivos CSV existan
            $files = [
                'bullying_incidents.csv',
                'cyberbullying_detection_dataset.csv',
                'cyberbullying_tweets.csv'
            ];
            
            foreach ($files as $file) {
                $path = public_path('data/' . $file);
                if (!File::exists($path)) {
                    return response()->json([
                        'error' => 'Archivo no encontrado: ' . $file
                    ], 404);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Datos CSV disponibles y listos para análisis',
                'files_processed' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al verificar datos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}