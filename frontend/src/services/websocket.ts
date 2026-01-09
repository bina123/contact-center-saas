import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo: Echo;
  }
}

window.Pusher = Pusher;

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:6001';
const APP_KEY = import.meta.env.VITE_REVERB_APP_KEY || 'local-app-key';

export class WebSocketService {
  private echo: Echo | null = null;
  private tenantId: string | null = null;

  initialize(tenantId: string) {
    this.tenantId = tenantId;
    
    this.echo = new Echo({
      broadcaster: 'reverb',
      key: APP_KEY,
      wsHost: WS_URL.replace('ws://', '').replace('wss://', ''),
      wsPort: 6001,
      wssPort: 6001,
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/api/broadcasting/auth',
      auth: {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      },
    });

    window.Echo = this.echo;
  }

  disconnect() {
    if (this.echo) {
      this.echo.disconnect();
      this.echo = null;
    }
  }

  // Subscribe to tenant-specific call events
  subscribeToCallEvents(
    callbacks: {
      onCallStarted?: (call: any) => void;
      onCallAnswered?: (call: any) => void;
      onCallEnded?: (call: any) => void;
    }
  ) {
    if (!this.echo || !this.tenantId) return;

    const channel = this.echo.channel(`tenant.${this.tenantId}.calls`);

    if (callbacks.onCallStarted) {
      channel.listen('.call.started', callbacks.onCallStarted);
    }

    if (callbacks.onCallAnswered) {
      channel.listen('.call.answered', callbacks.onCallAnswered);
    }

    if (callbacks.onCallEnded) {
      channel.listen('.call.ended', callbacks.onCallEnded);
    }

    return () => {
      this.echo?.leaveChannel(`tenant.${this.tenantId}.calls`);
    };
  }

  // Subscribe to agent presence channel
  subscribeToAgentPresence(
    callbacks: {
      onJoin?: (agent: any) => void;
      onLeave?: (agent: any) => void;
    }
  ) {
    if (!this.echo || !this.tenantId) return;

    const channel = this.echo.join(`tenant.${this.tenantId}.agents`);

    if (callbacks.onJoin) {
      channel.here((agents: any[]) => {
        agents.forEach(callbacks.onJoin);
      }).joining(callbacks.onJoin);
    }

    if (callbacks.onLeave) {
      channel.leaving(callbacks.onLeave);
    }

    return () => {
      this.echo?.leave(`tenant.${this.tenantId}.agents`);
    };
  }

  // Subscribe to dashboard metrics updates
  subscribeToDashboardMetrics(callback: (metrics: any) => void) {
    if (!this.echo || !this.tenantId) return;

    const channel = this.echo.channel(`tenant.${this.tenantId}.dashboard`);
    channel.listen('.metrics.updated', callback);

    return () => {
      this.echo?.leaveChannel(`tenant.${this.tenantId}.dashboard`);
    };
  }

  // Subscribe to ticket updates
  subscribeToTicketEvents(
    callbacks: {
      onTicketCreated?: (ticket: any) => void;
      onTicketAssigned?: (ticket: any) => void;
      onTicketUpdated?: (ticket: any) => void;
    }
  ) {
    if (!this.echo || !this.tenantId) return;

    const channel = this.echo.channel(`tenant.${this.tenantId}.tickets`);

    if (callbacks.onTicketCreated) {
      channel.listen('.ticket.created', callbacks.onTicketCreated);
    }

    if (callbacks.onTicketAssigned) {
      channel.listen('.ticket.assigned', callbacks.onTicketAssigned);
    }

    if (callbacks.onTicketUpdated) {
      channel.listen('.ticket.updated', callbacks.onTicketUpdated);
    }

    return () => {
      this.echo?.leaveChannel(`tenant.${this.tenantId}.tickets`);
    };
  }
}

export const wsService = new WebSocketService();
