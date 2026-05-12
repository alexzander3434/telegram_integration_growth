export type Shop = {
  id: number;
  name: string;
};

export type TelegramConnectPayload = {
  botToken: string;
  chatId: string;
  enabled: boolean;
};

export type TelegramIntegrationStatus = {
  enabled: boolean;
  chatId: string | null;
  lastSentAt: string | null;
  sentCount: number;
  failedCount: number;
  lastFailedAt: string | null;
  lastError: string | null;
};

export type CreateOrderPayload = {
  number: string;
  total: number;
  customerName: string;
};


