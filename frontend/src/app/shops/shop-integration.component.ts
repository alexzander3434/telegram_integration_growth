import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ApiService } from '../api.service';
import type { TelegramIntegrationStatus } from '../api.types';

@Component({
  selector: 'app-shop-integration',
  standalone: true,
  imports: [RouterLink, ReactiveFormsModule],
  template: `
    <div class="mb-6 flex items-start justify-between gap-4">
      <div>
        <a routerLink="/shops" class="text-sm text-slate-600 hover:text-slate-900">
          ← К списку магазинов
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Магазин {{ shopId() }}</h1>
        <p class="mt-1 text-sm text-slate-600">
          Ниже — форма подключения и текущий статус (маскированный chat ID, успешные и ошибочные отправки за 7 дней).
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="rounded-2xl border bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold">Интеграция Telegram</h2>
            <p class="mt-1 text-sm text-slate-600">Подключите бота к этому магазину.</p>
          </div>
          <button
            type="button"
            (click)="loadTelegramStatus()"
            [disabled]="!shopIdValid() || telegramStatusLoading()"
            class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
          >
            @if (telegramStatusLoading()) { Обновление… } @else { Обновить статус }
          </button>
        </div>

        <div class="mb-5 rounded-xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-4 shadow-inner">
          <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Текущий статус</div>
          @if (telegramStatusLoading() && !telegramStatus()) {
            <div class="flex items-center gap-2 text-sm text-slate-500">
              <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600"></span>
              Загрузка…
            </div>
          } @else if (telegramStatusError()) {
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ telegramStatusError() }}
            </div>
          } @else if (telegramStatus()) {
            @let s = telegramStatus()!;
            <div class="mb-3 flex flex-wrap items-center gap-2">
              @if (s.chatId === null) {
                <span
                  class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-800"
                  >Не настроено</span>
              } @else if (s.enabled) {
                <span
                  class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800"
                  >Включена</span>
              } @else {
                <span
                  class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900"
                  >Отключена</span>
              }
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div class="rounded-lg border border-slate-100 bg-white/80 px-3 py-2">
                <dt class="text-xs text-slate-500">Chat ID (маскированный)</dt>
                <dd class="mt-0.5 font-mono text-sm font-medium text-slate-900">{{ s.chatId ?? '—' }}</dd>
              </div>
              <div class="rounded-lg border border-slate-100 bg-white/80 px-3 py-2">
                <dt class="text-xs text-slate-500">Последняя успешная отправка</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ s.lastSentAt ?? '—' }}</dd>
              </div>
              <div class="rounded-lg border border-slate-100 bg-white/80 px-3 py-2">
                <dt class="text-xs text-slate-500">Успешно за 7 дней</dt>
                <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ s.sentCount }}</dd>
              </div>
              <div class="rounded-lg border border-slate-100 bg-white/80 px-3 py-2">
                <dt class="text-xs text-slate-500">Ошибок за 7 дней</dt>
                <dd
                  class="mt-0.5 text-lg font-semibold tabular-nums"
                  [class.text-red-600]="s.failedCount > 0"
                  [class.text-slate-900]="s.failedCount === 0"
                >
                  {{ s.failedCount }}
                </dd>
              </div>
            </dl>
          }
        </div>

        <form [formGroup]="integrationForm" (ngSubmit)="onSubmitIntegration()" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-700">Токен бота</label>
          <input
            formControlName="botToken"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-xl border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300"
            placeholder="123456:ABCDEF..."
          />
          @if (integrationForm.controls.botToken.touched && integrationForm.controls.botToken.invalid) {
            <div class="mt-1 text-xs text-red-600">Укажите токен бота</div>
          }
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">ID чата</label>
          <input
            formControlName="chatId"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-xl border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300"
            placeholder="-1001234567890"
          />
          @if (integrationForm.controls.chatId.touched && integrationForm.controls.chatId.invalid) {
            <div class="mt-1 text-xs text-red-600">Укажите ID чата</div>
          }
        </div>

        <label class="flex items-center gap-3">
          <input
            formControlName="enabled"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-300"
          />
          <span class="text-sm text-slate-700">Включено</span>
        </label>

        @if (integrationSuccess()) {
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            Интеграция создана.
          </div>
        }

        @if (integrationError()) {
          <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ integrationError() }}
          </div>
        }

        <div class="flex items-center gap-3">
          <button
            type="submit"
            [disabled]="integrationForm.invalid || submittingIntegration() || !shopIdValid()"
            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
          >
            @if (submittingIntegration()) { Сохранение… } @else { Подключить }
          </button>
          <div class="text-xs text-slate-500">
            POST /shops/{{ shopId() }}/telegram/connect
          </div>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <div class="mb-4">
        <h2 class="text-lg font-semibold">Создать заказ</h2>
        <p class="mt-1 text-sm text-slate-600">Новый заказ для этого магазина.</p>
      </div>

      <form [formGroup]="orderForm" (ngSubmit)="onSubmitOrder()" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-700">Номер</label>
          <input
            formControlName="number"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-xl border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300"
            placeholder="A-1005"
          />
          @if (orderForm.controls.number.touched && orderForm.controls.number.invalid) {
            <div class="mt-1 text-xs text-red-600">Укажите номер</div>
          }
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Сумма, ₽</label>
          <input
            formControlName="total"
            type="number"
            inputmode="decimal"
            class="mt-1 w-full rounded-xl border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300"
            placeholder="2490"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Имя клиента</label>
          <input
            formControlName="customerName"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-xl border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300"
            placeholder="Анна"
          />
          @if (orderForm.controls.customerName.touched && orderForm.controls.customerName.invalid) {
            <div class="mt-1 text-xs text-red-600">Укажите имя клиента</div>
          }
        </div>

        @if (orderSuccess()) {
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            Заказ создан.
          </div>
        }

        @if (orderError()) {
          <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ orderError() }}
          </div>
        }

        <div class="flex items-center gap-3">
          <button
            type="submit"
            [disabled]="orderForm.invalid || submittingOrder() || !shopIdValid()"
            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
          >
            @if (submittingOrder()) { Сохранение… } @else { Создать заказ }
          </button>
          <div class="text-xs text-slate-500">POST /shops/{{ shopId() }}/orders</div>
        </div>
      </form>
    </div>
  </div>
  `,
})
export class ShopIntegrationComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(ApiService);

  readonly shopId = signal<number>(NaN);
  readonly submittingIntegration = signal(false);
  readonly integrationSuccess = signal(false);
  readonly integrationError = signal<string | null>(null);

  readonly submittingOrder = signal(false);
  readonly orderSuccess = signal(false);
  readonly orderError = signal<string | null>(null);

  readonly telegramStatus = signal<TelegramIntegrationStatus | null>(null);
  readonly telegramStatusLoading = signal(false);
  readonly telegramStatusError = signal<string | null>(null);

  readonly shopIdValid = computed(() => Number.isFinite(this.shopId()));

  readonly integrationForm = this.fb.nonNullable.group({
    botToken: ['', [Validators.required]],
    chatId: ['', [Validators.required]],
    enabled: true,
  });

  readonly orderForm = this.fb.nonNullable.group({
    number: ['', [Validators.required]],
    total: 0,
    customerName: ['', [Validators.required]],
  });

  constructor() {
    const raw = this.route.snapshot.paramMap.get('shopId');
    this.shopId.set(raw ? Number(raw) : NaN);
    this.loadTelegramStatus();
  }

  loadTelegramStatus(): void {
    if (!this.shopIdValid()) {
      return;
    }
    this.telegramStatusLoading.set(true);
    this.telegramStatusError.set(null);
    this.api.getTelegramStatus(this.shopId()).subscribe({
      next: (data) => {
        this.telegramStatus.set(data);
        this.telegramStatusLoading.set(false);
      },
      error: (err) => {
        this.telegramStatus.set(null);
        this.telegramStatusError.set(
          err?.error?.message ?? err?.message ?? 'Не удалось загрузить статус Telegram',
        );
        this.telegramStatusLoading.set(false);
      },
    });
  }

  onSubmitIntegration(): void {
    this.integrationSuccess.set(false);
    this.integrationError.set(null);

    if (!this.shopIdValid()) {
      this.integrationError.set('Некорректный номер магазина');
      return;
    }
    if (this.integrationForm.invalid) {
      this.integrationForm.markAllAsTouched();
      return;
    }

    this.submittingIntegration.set(true);
    this.api
      .connectTelegram(this.shopId(), this.integrationForm.getRawValue())
      .subscribe({
        next: () => {
          this.integrationSuccess.set(true);
          this.submittingIntegration.set(false);
          this.loadTelegramStatus();
        },
        error: (err) => {
          const status = err?.status;
          if (status === 409) {
            this.integrationError.set('Для этого магазина интеграция уже существует.');
          } else if (status === 422) {
            this.integrationError.set('Проверьте корректность введённых данных.');
          } else {
            this.integrationError.set(err?.message ?? 'Не удалось создать интеграцию');
          }
          this.submittingIntegration.set(false);
        },
      });
  }

  onSubmitOrder(): void {
    this.orderSuccess.set(false);
    this.orderError.set(null);

    if (!this.shopIdValid()) {
      this.orderError.set('Некорректный номер магазина');
      return;
    }
    if (this.orderForm.invalid) {
      this.orderForm.markAllAsTouched();
      return;
    }

    const payload = this.orderForm.getRawValue();
    if (payload.total === null || payload.total === undefined || Number.isNaN(Number(payload.total))) {
      this.orderError.set('Укажите сумму числом');
      return;
    }

    this.submittingOrder.set(true);
    this.api.createOrder(this.shopId(), { ...payload, total: Number(payload.total) }).subscribe({
      next: () => {
        this.orderSuccess.set(true);
        this.submittingOrder.set(false);
        this.loadTelegramStatus();
      },
      error: (err) => {
        const status = err?.status;
        if (status === 409) {
          this.orderError.set('Заказ с таким номером уже существует.');
        } else if (status === 422) {
          this.orderError.set('Проверьте корректность введённых данных.');
        } else {
          this.orderError.set(err?.message ?? 'Не удалось создать заказ');
        }
        this.submittingOrder.set(false);
      },
    });
  }
}

