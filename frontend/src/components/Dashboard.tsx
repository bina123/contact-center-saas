import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiService } from '@/services/api';
import { wsService } from '@/services/websocket';
import {
  Phone,
  PhoneIncoming,
  PhoneMissed,
  Users,
  Clock,
  TrendingUp,
  Activity,
} from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface DashboardMetrics {
  real_time: {
    active_agents: number;
    active_calls: number;
    queued_calls: number;
  };
  today: {
    total_calls: number;
    answered_calls: number;
    missed_calls: number;
    answer_rate: number;
    avg_call_duration: number;
    avg_wait_time: number;
    tickets_created: number;
  };
}

export function Dashboard() {
  const [liveMetrics, setLiveMetrics] = useState<DashboardMetrics | null>(null);

  // Fetch initial dashboard data
  const { data: metrics, refetch } = useQuery({
    queryKey: ['dashboard-overview'],
    queryFn: () => apiService.getDashboardOverview(),
    refetchInterval: 30000, // Refetch every 30 seconds
  });

  const { data: callVolume } = useQuery({
    queryKey: ['call-volume'],
    queryFn: () => apiService.getCallVolumeChart(7),
  });

  const { data: agentPerformance } = useQuery({
    queryKey: ['agent-performance'],
    queryFn: () => apiService.getAgentPerformance(),
  });

  // Subscribe to real-time updates
  useEffect(() => {
    const unsubscribe = wsService.subscribeToDashboardMetrics((updatedMetrics) => {
      setLiveMetrics(updatedMetrics);
      refetch();
    });

    return () => {
      if (unsubscribe) unsubscribe();
    };
  }, [refetch]);

  const currentMetrics = liveMetrics || metrics;

  if (!currentMetrics) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="p-6 space-y-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-500 mt-1">Real-time contact center overview</p>
        </div>
        <div className="flex items-center space-x-2 text-sm text-gray-500">
          <Activity className="w-4 h-4 text-green-500 animate-pulse" />
          <span>Live</span>
        </div>
      </div>

      {/* Real-time Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <StatCard
          title="Active Agents"
          value={currentMetrics.real_time.active_agents}
          icon={<Users className="w-8 h-8 text-primary-600" />}
          color="primary"
          trend="live"
        />
        <StatCard
          title="Active Calls"
          value={currentMetrics.real_time.active_calls}
          icon={<Phone className="w-8 h-8 text-green-600" />}
          color="green"
          trend="live"
        />
        <StatCard
          title="Queued Calls"
          value={currentMetrics.real_time.queued_calls}
          icon={<Clock className="w-8 h-8 text-yellow-600" />}
          color="yellow"
          trend="live"
        />
      </div>

      {/* Today's Performance */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Today's Performance</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          <MetricItem
            label="Total Calls"
            value={currentMetrics.today.total_calls}
            icon={<Phone className="w-5 h-5" />}
          />
          <MetricItem
            label="Answered"
            value={currentMetrics.today.answered_calls}
            icon={<PhoneIncoming className="w-5 h-5 text-green-600" />}
            percentage={`${currentMetrics.today.answer_rate}%`}
          />
          <MetricItem
            label="Missed"
            value={currentMetrics.today.missed_calls}
            icon={<PhoneMissed className="w-5 h-5 text-red-600" />}
          />
          <MetricItem
            label="Avg Duration"
            value={formatDuration(currentMetrics.today.avg_call_duration)}
            icon={<Clock className="w-5 h-5" />}
          />
        </div>
      </div>

      {/* Call Volume Chart */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Call Volume (Last 7 Days)</h2>
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={callVolume || []}>
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            <XAxis dataKey="date" stroke="#6b7280" />
            <YAxis stroke="#6b7280" />
            <Tooltip
              contentStyle={{
                backgroundColor: '#fff',
                border: '1px solid #e5e7eb',
                borderRadius: '8px',
              }}
            />
            <Line
              type="monotone"
              dataKey="total"
              stroke="#0ea5e9"
              strokeWidth={2}
              dot={{ fill: '#0ea5e9' }}
            />
            <Line
              type="monotone"
              dataKey="completed"
              stroke="#10b981"
              strokeWidth={2}
              dot={{ fill: '#10b981' }}
            />
            <Line
              type="monotone"
              dataKey="missed"
              stroke="#ef4444"
              strokeWidth={2}
              dot={{ fill: '#ef4444' }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>

      {/* Agent Performance */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Agent Performance</h2>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                  Agent
                </th>
                <th className="text-center py-3 px-4 text-sm font-semibold text-gray-700">
                  Status
                </th>
                <th className="text-center py-3 px-4 text-sm font-semibold text-gray-700">
                  Total Calls
                </th>
                <th className="text-center py-3 px-4 text-sm font-semibold text-gray-700">
                  Completed
                </th>
                <th className="text-center py-3 px-4 text-sm font-semibold text-gray-700">
                  Avg Duration
                </th>
              </tr>
            </thead>
            <tbody>
              {agentPerformance?.map((agent: any) => (
                <tr key={agent.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="py-3 px-4">
                    <div className="flex items-center space-x-2">
                      <div className="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                        <span className="text-sm font-medium text-primary-700">
                          {agent.name.charAt(0)}
                        </span>
                      </div>
                      <span className="font-medium text-gray-900">{agent.name}</span>
                    </div>
                  </td>
                  <td className="py-3 px-4 text-center">
                    <span
                      className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                        agent.is_online
                          ? 'bg-green-100 text-green-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {agent.is_online ? 'Online' : 'Offline'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-center text-gray-700">
                    {agent.total_calls}
                  </td>
                  <td className="py-3 px-4 text-center text-gray-700">
                    {agent.completed_calls}
                  </td>
                  <td className="py-3 px-4 text-center text-gray-700">
                    {formatDuration(Math.round(agent.avg_duration))}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function StatCard({
  title,
  value,
  icon,
  color,
  trend,
}: {
  title: string;
  value: number;
  icon: React.ReactNode;
  color: string;
  trend?: string;
}) {
  const colorClasses = {
    primary: 'from-primary-50 to-primary-100 border-primary-200',
    green: 'from-green-50 to-green-100 border-green-200',
    yellow: 'from-yellow-50 to-yellow-100 border-yellow-200',
  };

  return (
    <div
      className={`bg-gradient-to-br ${
        colorClasses[color as keyof typeof colorClasses]
      } border rounded-lg p-6 shadow-sm`}
    >
      <div className="flex items-center justify-between mb-4">
        {icon}
        {trend === 'live' && (
          <span className="flex items-center space-x-1 text-xs font-medium text-gray-600">
            <div className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            <span>LIVE</span>
          </span>
        )}
      </div>
      <div className="space-y-1">
        <p className="text-3xl font-bold text-gray-900">{value}</p>
        <p className="text-sm font-medium text-gray-600">{title}</p>
      </div>
    </div>
  );
}

function MetricItem({
  label,
  value,
  icon,
  percentage,
}: {
  label: string;
  value: string | number;
  icon: React.ReactNode;
  percentage?: string;
}) {
  return (
    <div className="space-y-2">
      <div className="flex items-center space-x-2 text-gray-500">{icon}</div>
      <div>
        <p className="text-2xl font-bold text-gray-900">{value}</p>
        <div className="flex items-center space-x-2">
          <p className="text-sm text-gray-600">{label}</p>
          {percentage && (
            <span className="text-xs font-medium text-green-600 flex items-center">
              <TrendingUp className="w-3 h-3 mr-1" />
              {percentage}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}
