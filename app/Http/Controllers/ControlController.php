<?php

namespace App\Http\Controllers;

use App\Events\LiveStateUpdated;
use App\Models\LiveState;
use App\Models\Mass;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ControlController extends Controller
{
    public function page()
    {
        return view('control', ['client' => OutputController::clientConfig()]);
    }

    public function state(): JsonResponse
    {
        return response()->json(LiveState::current()->payload());
    }

    public function masses(): JsonResponse
    {
        $masses = Mass::query()
            ->orderByDesc('is_template')
            ->orderByDesc('celebrated_at')
            ->limit(100)
            ->get()
            ->map(fn (Mass $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'celebrated_at' => $m->celebrated_at?->format('d/m H:i'),
                'priest' => $m->priest,
                'is_template' => $m->is_template,
            ]);

        return response()->json($masses);
    }

    public function rundown(Mass $mass): JsonResponse
    {
        $mass->load(['items.libraryItem', 'theme']);

        return response()->json($mass->toRundownArray());
    }

    public function themes(): JsonResponse
    {
        return response()->json(
            Theme::query()->orderBy('id')->get()->map(fn (Theme $t) => $t->toPayload())
        );
    }

    public function action(string $action, Request $request): JsonResponse
    {
        $pin = (string) (config('liturgia.pin') ?? '');
        if ($pin !== '' && (string) $request->input('pin') !== $pin) {
            return response()->json(['error' => 'PIN salah'], 403);
        }

        $state = LiveState::current();

        switch ($action) {
            case 'mass':
                $state->mass_id = $request->integer('mass_id') ?: null;
                $state->item_index = 0;
                $state->block_index = 0;
                $state->quick = null;
                $state->mode = 'both';
                break;

            case 'goto':
                $state->item_index = max(0, $request->integer('item'));
                $state->block_index = max(0, $request->integer('block'));
                $state->quick = null;
                if ($state->mode === 'clear') {
                    $state->mode = 'both';
                }
                break;

            case 'next':
            case 'prev':
                $this->step($state, $action === 'next' ? 1 : -1);
                break;

            case 'mode':
                $mode = $request->input('mode', 'both');
                if (in_array($mode, ['both', 'full', 'lower', 'clear'], true)) {
                    $state->mode = $mode;
                }
                break;

            case 'preset':
                $preset = $request->input('preset', 'scrim');
                if (in_array($preset, [
                    'transparan', 'scrim', 'glass', 'solid',
                    'emas', 'reveal', 'bertingkat', 'pita', 'panel',
                    'timpa', 'plakat',
                ], true)) {
                    $state->preset = $preset;
                }
                break;

            case 'align':
                $align = $request->input('align', 'center');
                if (in_array($align, ['left', 'center', 'right'], true)) {
                    $state->align = $align;
                }
                break;

            case 'badge':
                $badge = $request->input('badge', 'accent');
                if (in_array($badge, ['accent', 'gold', 'silver', 'emerald'], true)) {
                    $state->badge = $badge;
                }
                break;

            case 'theme':
                $state->theme_id = $request->integer('theme_id') ?: null;
                break;

            case 'quick':
                $state->quick = [
                    'header' => (string) $request->input('header', ''),
                    'text' => (string) $request->input('text', ''),
                    'target' => in_array($request->input('target'), ['full', 'lower', 'both'], true)
                        ? $request->input('target') : 'both',
                ];
                if ($state->mode === 'clear') {
                    $state->mode = 'both';
                }
                break;

            case 'clear':
                $state->quick = null;
                $state->mode = 'clear';
                break;
        }

        $state->updated_by = (string) $request->input('operator', '');
        $state->save();
        $state->refresh();

        $payload = $state->payload();
        broadcast(new LiveStateUpdated($payload));

        return response()->json($payload);
    }

    /** Maju/mundur satu blok; meluap ke item berikut/sebelumnya. */
    private function step(LiveState $state, int $dir): void
    {
        $mass = $state->mass?->load(['items.libraryItem', 'theme']);
        if (! $mass) {
            return;
        }

        $items = $mass->toRundownArray()['items'];
        if ($items === []) {
            return;
        }

        $i = min((int) $state->item_index, count($items) - 1);
        $b = (int) $state->block_index + $dir;

        if ($dir > 0) {
            while ($i < count($items) && $b >= count($items[$i]['blocks'])) {
                $i++;
                $b = 0;
            }
            if ($i >= count($items)) {
                $i = count($items) - 1;
                $b = count($items[$i]['blocks']) - 1;
            }
        } else {
            while ($i >= 0 && $b < 0) {
                $i--;
                $b = $i >= 0 ? count($items[$i]['blocks']) - 1 : 0;
            }
            if ($i < 0) {
                $i = 0;
                $b = 0;
            }
        }

        $state->item_index = $i;
        $state->block_index = max(0, $b);
        $state->quick = null;
        if ($state->mode === 'clear') {
            $state->mode = 'both';
        }
    }
}
