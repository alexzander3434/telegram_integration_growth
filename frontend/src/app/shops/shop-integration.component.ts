import { Component, computed, effect, inject, input, signal, untracked } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ApiService } from '../api.service';
import type { TelegramIntegrationStatus } from '../api.types';

@Component({
  selector: 'app-shop-integration',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './shop-integration.component.html',
})
export class ShopIntegrationComponent {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(ApiService);

  /** Передаётся с экрана `app-shop-dashboard` */
  readonly shopId = input.required<number>();

  readonly submittingIntegration = signal(false);
  readonly integrationSuccess = signal(false);
  readonly integrationError = signal<string | null>(null);

  readonly telegramStatus = signal<TelegramIntegrationStatus | null>(null);
  readonly telegramStatusLoading = signal(false);
  readonly telegramStatusError = signal<string | null>(null);

  readonly shopIdValid = computed(() => Number.isFinite(this.shopId()));

  readonly integrationForm = this.fb.nonNullable.group({
    botToken: ['', [Validators.required]],
    chatId: ['', [Validators.required]],
    enabled: true,
  });

  constructor() {
    effect(() => {
      const id = this.shopId();
      if (!Number.isFinite(id)) {
        return;
      }
      untracked(() => this.loadTelegramStatus());
    });
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
          const body = err?.error;
          const tokenMsg =
            typeof body?.message === 'string' && body.message.trim() !== ''
              ? body.message
              : null;
          if (status === 409) {
            this.integrationError.set('Конфликт при сохранении интеграции.');
          } else if (status === 422) {
            if (tokenMsg) {
              this.integrationError.set(tokenMsg);
            } else if (Array.isArray(body?.details) && body.details.length > 0) {
              this.integrationError.set(
                body.details.map((d: { message?: string }) => d.message ?? '').filter(Boolean).join(' '),
              );
            } else {
              this.integrationError.set('Проверьте корректность введённых данных.');
            }
          } else {
            this.integrationError.set(err?.message ?? 'Не удалось создать интеграцию');
          }
          this.submittingIntegration.set(false);
        },
      });
  }
}
