<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * Statistics Calculator - Centralized metrics calculations
 * 
 * Eliminates redundant calculation patterns and provides
 * consistent statistical analysis across the framework.
 */
#[Injectable(dependencies: [])]
final class StatisticsCalculator
{
    /**
     * Calculate hit rate percentage
     */
    public static function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Calculate operations per second
     */
    public static function calculateOpsPerSecond(int $totalOps, float $timeSeconds): float
    {
        return $timeSeconds > 0 ? round($totalOps / $timeSeconds, 2) : 0.0;
    }

    /**
     * Calculate average response time
     */
    public static function calculateAverageResponseTime(float $totalTime, int $operationCount): float
    {
        return $operationCount > 0 ? round($totalTime / $operationCount, 4) : 0.0;
    }

    /**
     * Calculate memory fragmentation ratio
     */
    public static function calculateMemoryFragmentation(int $usedMemory, int $allocatedMemory): float
    {
        return $allocatedMemory > 0 ? round($usedMemory / $allocatedMemory, 2) : 1.0;
    }

    /**
     * Calculate usage ratio
     */
    public static function calculateUsageRatio(int $current, int $maximum): float
    {
        return $maximum > 0 ? round($current / $maximum, 4) : 0.0;
    }

    /**
     * Calculate percentage
     */
    public static function calculatePercentage(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0.0;
    }

    /**
     * Calculate throughput (items per time unit)
     */
    public static function calculateThroughput(int $items, float $timeSeconds): float
    {
        return $timeSeconds > 0 ? round($items / $timeSeconds, 2) : 0.0;
    }

    /**
     * Calculate moving average
     */
    public static function calculateMovingAverage(array $values, int $windowSize): array
    {
        $result = [];
        $count = count($values);
        
        for ($i = 0; $i < $count; $i++) {
            $start = max(0, $i - $windowSize + 1);
            $window = array_slice($values, $start, $i - $start + 1);
            $result[] = count($window) > 0 ? array_sum($window) / count($window) : 0;
        }
        
        return $result;
    }

    /**
     * Calculate standard deviation
     */
    public static function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count === 0) return 0.0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $count;
        
        return round(sqrt($variance), 4);
    }

    /**
     * Calculate percentile
     */
    public static function calculatePercentile(array $values, float $percentile): float
    {
        if (empty($values)) return 0.0;
        
        sort($values);
        $count = count($values);
        $index = ($percentile / 100) * ($count - 1);
        
        if (floor($index) === $index) {
            return $values[(int)$index];
        }
        
        $lower = $values[(int)floor($index)];
        $upper = $values[(int)ceil($index)];
        $fraction = $index - floor($index);
        
        return round($lower + ($fraction * ($upper - $lower)), 4);
    }

    /**
     * Build comprehensive statistics array
     */
    public static function buildStatsArray(array $data, array $config = []): array
    {
        $stats = [
            'timestamp' => PerformanceTimer::now(),
            'data_points' => count($data)
        ];

        // Basic calculations
        if (isset($data['hits'], $data['misses'])) {
            $stats['hit_rate'] = self::calculateHitRate($data['hits'], $data['misses']);
        }

        if (isset($data['total_ops'], $data['uptime'])) {
            $stats['ops_per_second'] = self::calculateOpsPerSecond($data['total_ops'], $data['uptime']);
        }

        if (isset($data['used_memory'], $data['allocated_memory'])) {
            $stats['memory_fragmentation'] = self::calculateMemoryFragmentation(
                $data['used_memory'],
                $data['allocated_memory']
            );
        }

        if (isset($data['current'], $data['maximum'])) {
            $stats['usage_ratio'] = self::calculateUsageRatio($data['current'], $data['maximum']);
            $stats['usage_percentage'] = self::calculatePercentage($data['current'], $data['maximum']);
        }

        // Performance metrics
        if (isset($data['response_times']) && is_array($data['response_times'])) {
            $responseTimes = $data['response_times'];
            $stats['avg_response_time'] = self::calculateAverageResponseTime(
                array_sum($responseTimes),
                count($responseTimes)
            );
            $stats['response_time_p95'] = self::calculatePercentile($responseTimes, 95);
            $stats['response_time_p99'] = self::calculatePercentile($responseTimes, 99);
            $stats['response_time_std_dev'] = self::calculateStandardDeviation($responseTimes);
        }

        // Throughput calculations
        if (isset($data['items'], $data['duration'])) {
            $stats['throughput'] = self::calculateThroughput($data['items'], $data['duration']);
        }

        return $stats;
    }

    /**
     * Calculate health score based on multiple metrics
     */
    public static function calculateHealthScore(array $metrics, array $weights = []): float
    {
        if (empty($metrics)) return 0.0;

        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($metrics as $metric => $value) {
            $weight = $weights[$metric] ?? 1.0;
            $score = self::normalizeMetricValue($metric, $value);
            
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 2) : 0.0;
    }

    /**
     * Normalize metric value to 0-100 scale
     */
    public static function normalizeMetricValue(string $metric, mixed $value): float
    {
        // Normalize different types of metrics to 0-100 scale
        switch ($metric) {
            case 'hit_rate':
                return min(100, max(0, (float)$value));
                
            case 'usage_ratio':
                // Invert usage ratio (lower is better)
                return max(0, 100 - ((float)$value * 100));
                
            case 'response_time':
                // Lower response time is better (assume 1s = 0 score, 0s = 100 score)
                return max(0, 100 - ((float)$value * 100));
                
            case 'error_rate':
                // Lower error rate is better
                return max(0, 100 - (float)$value);
                
            case 'availability':
                return min(100, max(0, (float)$value));
                
            default:
                // Default normalization for unknown metrics
                return min(100, max(0, (float)$value));
        }
    }

    /**
     * Calculate trend from time series data
     */
    public static function calculateTrend(array $timeSeries): array
    {
        $count = count($timeSeries);
        if ($count < 2) {
            return ['direction' => 'stable', 'slope' => 0, 'confidence' => 0];
        }

        // Simple linear regression
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($timeSeries as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($count * $sumXY - $sumX * $sumY) / ($count * $sumX2 - $sumX * $sumX);
        $confidence = abs($slope) > 0.1 ? min(100, abs($slope) * 10) : 0;

        $direction = 'stable';
        if ($slope > 0.1) {
            $direction = 'increasing';
        } elseif ($slope < -0.1) {
            $direction = 'decreasing';
        }

        return [
            'direction' => $direction,
            'slope' => round($slope, 4),
            'confidence' => round($confidence, 2)
        ];
    }

    /**
     * Calculate burst detection
     */
    public static function detectBurst(array $values, float $threshold = 2.0): array
    {
        if (count($values) < 3) {
            return ['burst_detected' => false, 'burst_points' => []];
        }

        $mean = array_sum($values) / count($values);
        $stdDev = self::calculateStandardDeviation($values);
        $burstThreshold = $mean + ($threshold * $stdDev);
        
        $burstPoints = [];
        foreach ($values as $index => $value) {
            if ($value > $burstThreshold) {
                $burstPoints[] = [
                    'index' => $index,
                    'value' => $value,
                    'threshold' => $burstThreshold,
                    'deviation' => round(($value - $mean) / $stdDev, 2)
                ];
            }
        }

        return [
            'burst_detected' => !empty($burstPoints),
            'burst_points' => $burstPoints,
            'burst_threshold' => round($burstThreshold, 2),
            'mean' => round($mean, 2),
            'std_dev' => $stdDev
        ];
    }

    /**
     * Calculate correlation between two data sets
     */
    public static function calculateCorrelation(array $x, array $y): float
    {
        $count = min(count($x), count($y));
        if ($count < 2) return 0.0;

        $meanX = array_sum(array_slice($x, 0, $count)) / $count;
        $meanY = array_sum(array_slice($y, 0, $count)) / $count;

        $numerator = 0;
        $sumSqX = 0;
        $sumSqY = 0;

        for ($i = 0; $i < $count; $i++) {
            $devX = $x[$i] - $meanX;
            $devY = $y[$i] - $meanY;
            
            $numerator += $devX * $devY;
            $sumSqX += $devX * $devX;
            $sumSqY += $devY * $devY;
        }

        $denominator = sqrt($sumSqX * $sumSqY);
        return $denominator > 0 ? round($numerator / $denominator, 4) : 0.0;
    }
}
