<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Call;
use App\Models\SavedChart;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChartController extends Controller
{
    public function __construct(private readonly AIService $aiService) {}

    public function generate(Request $request)
    {
        $data = $request->validate([
            'prompt' => ['required', 'string'],
        ]);

        $config = $this->aiService->generateChartConfig($data['prompt']);
        $chart = $this->buildChartFromConfig($config);
        return response()->json($chart);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ai_configuration' => ['required', 'array'],
        ]);

        $saved = SavedChart::create($data);
        return response()->json($saved, 201);
    }

    public function list()
    {
        return SavedChart::latest('id')->get();
    }

    public function rerun(Request $request, int $id)
    {
        $saved = SavedChart::findOrFail($id);
        $config = $saved->ai_configuration;

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $config['query']['time_filter'] = [
                'column' => data_get($config, 'query.time_filter.column', 'created_at'),
                'period' => null,
                'start_date' => $request->string('start_date')->toString() ?: null,
                'end_date' => $request->string('end_date')->toString() ?: null,
            ];
        }

        $chart = $this->buildChartFromConfig($config);
        return response()->json($chart);
    }

    public function drillDown(Request $request)
    {
        $data = $request->validate([
            'current_config' => ['required', 'array'],
            'drill_value' => ['required'],
        ]);

        $current = $data['current_config'];
        $groupBy = (string) data_get($current, 'query.group_by');
        $clicked = $data['drill_value'];

        $prompt = 'L\'utente sta visualizzando un grafico con group_by ' . $groupBy . ' e ha cliccato sul valore "' . $clicked . '". Genera SOLO un nuovo JSON di configurazione per un grafico di dettaglio pertinente.';

        $nextConfig = $this->aiService->generateChartConfig($prompt);

        // Impone filtro per il valore cliccato sul campo di group_by precedente
        $filters = (array) data_get($nextConfig, 'query.filters', []);
        $filters[] = [
            'field' => $groupBy,
            'operator' => '=',
            'value' => $clicked,
        ];
        data_set($nextConfig, 'query.filters', $filters);

        $chart = $this->buildChartFromConfig($nextConfig);
        return response()->json($chart);
    }

    private function buildChartFromConfig(array $config): array
    {
        $modelName = (string) data_get($config, 'query.model');
        $groupBy = (string) data_get($config, 'query.group_by');
        $aggregateFunction = (string) data_get($config, 'query.aggregate_function', 'count');
        $aggregateColumn = (string) data_get($config, 'query.aggregate_column', 'id');
        $filters = (array) data_get($config, 'query.filters', []);
        $timeFilter = (array) data_get($config, 'query.time_filter', []);

        $modelClass = match ($modelName) {
            'Lead' => Lead::class,
            'Call' => Call::class,
            default => throw new \InvalidArgumentException('Unsupported model: ' . $modelName),
        };

        $query = $modelClass::query();

        foreach ($filters as $filter) {
            $field = data_get($filter, 'field');
            $operator = data_get($filter, 'operator', '=');
            $value = data_get($filter, 'value');
            if ($field !== null) {
                if (strtolower((string) $operator) === 'in' && is_array($value)) {
                    $query->whereIn($field, $value);
                } elseif (strtolower((string) $operator) === 'like') {
                    $query->where($field, 'like', "%" . $value . "%");
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }

        if (!empty($timeFilter)) {
            $timeColumn = (string) data_get($timeFilter, 'column', 'created_at');
            $period = data_get($timeFilter, 'period');
            $start = data_get($timeFilter, 'start_date');
            $end = data_get($timeFilter, 'end_date');

            if ($start || $end) {
                if ($start) {
                    $query->where($timeColumn, '>=', $start);
                }
                if ($end) {
                    $query->where($timeColumn, '<=', $end);
                }
            } elseif ($period === 'last_7_days') {
                $query->where($timeColumn, '>=', now()->subDays(7));
            } elseif ($period === 'last_30_days') {
                $query->where($timeColumn, '>=', now()->subDays(30));
            } elseif ($period === 'last_12_months') {
                $query->where($timeColumn, '>=', now()->subMonths(12));
            }
        }

        $groupAlias = 'group_value';
        $aggAlias = 'aggregate_value';
        $query->selectRaw($groupBy . ' as ' . $groupAlias)
            ->selectRaw(strtoupper($aggregateFunction) . '(' . $aggregateColumn . ') as ' . $aggAlias)
            ->groupBy(DB::raw($groupBy))
            ->orderBy($groupAlias);

        $rows = $query->get();
        $labels = $rows->pluck($groupAlias)->map(function ($v) {
            return is_string($v) ? $v : (string) $v;
        })->all();
        $data = $rows->pluck($aggAlias)->map(fn ($v) => (float) $v)->all();

        return [
            'chart_type' => (string) data_get($config, 'chart_type', 'bar'),
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => (string) data_get($config, 'options.y_axis_label', 'Valore'),
                    'data' => $data,
                ]],
            ],
            'options' => [
                'title' => (string) data_get($config, 'options.title', ''),
                'description' => (string) data_get($config, 'options.description', ''),
                'x_axis_label' => (string) data_get($config, 'options.x_axis_label', ''),
                'y_axis_label' => (string) data_get($config, 'options.y_axis_label', ''),
            ],
            'config' => $config,
        ];
    }
}
