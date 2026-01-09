import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Request interceptor to add auth token
    this.api.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        const token = localStorage.getItem('auth_token');
        if (token && config.headers) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor for error handling
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  // Auth
  async login(email: string, password: string) {
    const response = await this.api.post('/login', { email, password });
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
    }
    return response.data;
  }

  async logout() {
    await this.api.post('/logout');
    localStorage.removeItem('auth_token');
  }

  async getCurrentUser() {
    const response = await this.api.get('/user');
    return response.data;
  }

  // Dashboard
  async getDashboardOverview() {
    const response = await this.api.get('/dashboard/overview');
    return response.data;
  }

  async getCallVolumeChart(days: number = 7) {
    const response = await this.api.get('/dashboard/call-volume', {
      params: { days },
    });
    return response.data;
  }

  async getAgentPerformance() {
    const response = await this.api.get('/dashboard/agent-performance');
    return response.data;
  }

  async getCallStatusDistribution() {
    const response = await this.api.get('/dashboard/call-status-distribution');
    return response.data;
  }

  async getRecentActivity(limit: number = 10) {
    const response = await this.api.get('/dashboard/recent-activity', {
      params: { limit },
    });
    return response.data;
  }

  // Calls
  async getCalls(params?: {
    page?: number;
    per_page?: number;
    status?: string;
    direction?: string;
  }) {
    const response = await this.api.get('/calls', { params });
    return response.data;
  }

  async getCall(id: number) {
    const response = await this.api.get(`/calls/${id}`);
    return response.data;
  }

  async getActiveCalls() {
    const response = await this.api.get('/calls/active');
    return response.data;
  }

  async getMyCalls(params?: { status?: string; direction?: string }) {
    const response = await this.api.get('/calls/my-calls', { params });
    return response.data;
  }

  async answerCall(id: number) {
    const response = await this.api.post(`/calls/${id}/answer`);
    return response.data;
  }

  async endCall(id: number, data: { notes?: string; status?: string }) {
    const response = await this.api.post(`/calls/${id}/end`, data);
    return response.data;
  }

  async updateCallNotes(id: number, notes: string) {
    const response = await this.api.patch(`/calls/${id}/notes`, { notes });
    return response.data;
  }

  // Tickets
  async getTickets(params?: { page?: number; status?: string }) {
    const response = await this.api.get('/tickets', { params });
    return response.data;
  }

  async getTicket(id: number) {
    const response = await this.api.get(`/tickets/${id}`);
    return response.data;
  }

  async createTicket(data: {
    subject: string;
    description: string;
    priority: string;
  }) {
    const response = await this.api.post('/tickets', data);
    return response.data;
  }

  async updateTicketStatus(id: number, status: string) {
    const response = await this.api.patch(`/tickets/${id}/status`, { status });
    return response.data;
  }

  async assignTicket(id: number, userId: number) {
    const response = await this.api.patch(`/tickets/${id}/assign`, {
      user_id: userId,
    });
    return response.data;
  }

  async addTicketMessage(id: number, message: string) {
    const response = await this.api.post(`/tickets/${id}/messages`, {
      message,
    });
    return response.data;
  }

  // Campaigns
  async getCampaigns() {
    const response = await this.api.get('/campaigns');
    return response.data;
  }

  async createCampaign(data: any) {
    const response = await this.api.post('/campaigns', data);
    return response.data;
  }

  async startCampaign(id: number) {
    const response = await this.api.post(`/campaigns/${id}/start`);
    return response.data;
  }

  // Users
  async getUsers() {
    const response = await this.api.get('/users');
    return response.data;
  }

  async createUser(data: any) {
    const response = await this.api.post('/users', data);
    return response.data;
  }

  async updateUserStatus(id: number, status: string) {
    const response = await this.api.patch(`/users/${id}/status`, { status });
    return response.data;
  }
}

export const apiService = new ApiService();
