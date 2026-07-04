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
                $state->paused = false;
                break;

            case 'goto':
                $state->item_index = max(0, $request->integer('item'));
                $state->block_index = max(0, $request->integer('block'));
                $state->quick = null;
                $state->paused = false;
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
                $state->paused = false;
                if ($state->mode === 'clear') {
                    $state->mode = 'both';
                }
                break;

            case 'clear':
                $state->quick = null;
                $state->paused = false;
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

    /**
     * Maju/mundur satu blok. Saat berpindah ke item/lagu berikutnya,
     * layar disembunyikan dulu (jeda) — Next berikutnya baru menampilkan.
     */
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

        $count = count($items);
        $i = min((int) $state->item_index, $count - 1);
        $b = (int) $state->block_index;
        $state->quick = null;

        if ($state->mode === 'clear') {
            $state->mode = 'both';
        }

        if ($dir > 0) {
            // Sedang jeda antar lagu → tampilkan item yang sudah menunggu
            if ($state->paused) {
                $state->paused = false;

                return;
            }

            if ($b + 1 < count($items[$i]['blocks'])) {
                $state->block_index = $b + 1;

                return;
            }

            // Blok terakhir item ini → pindah ke item berikut, tapi jeda dulu
            $next = $i + 1;
            while ($next < $count && count($items[$next]['blocks']) === 0) {
                $next++;
            }
            if ($next < $count) {
                $state->item_index = $next;
                $state->block_index = 0;
                $state->paused = true;
            }

            return;
        }

        // Mundur: kalau sedang jeda, kembali ke blok terakhir item sebelumnya
        if ($state->paused) {
            $state->paused = false;
            $prev = $i - 1;
            while ($prev >= 0 && count($items[$prev]['blocks']) === 0) {
                $prev--;
            }
            if ($prev >= 0) {
                $state->item_index = $prev;
                $state->block_index = count($items[$prev]['blocks']) - 1;
            }

            return;
        }

        if ($b - 1 >= 0) {
            $state->block_index = $b - 1;

            return;
        }

        $prev = $i - 1;
        while ($prev >= 0 && count($items[$prev]['blocks']) === 0) {
            $prev--;
        }
        if ($prev >= 0) {
            $state->item_index = $prev;
            $state->block_index = count($items[$prev]['blocks']) - 1;
        }
    }
}
