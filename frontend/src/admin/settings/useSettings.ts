import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { settingsService, type SettingsService } from './settingsService'
import type { Settings } from './types'

const settingsKey = ['admin', 'settings'] as const

export function useSettings(service: SettingsService = settingsService) {
  return useQuery({ queryKey: settingsKey, queryFn: () => service.get() })
}

export function useUpdateSettings(service: SettingsService = settingsService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (settings: Settings) => service.update(settings),
    onSuccess: (settings) => queryClient.setQueryData(settingsKey, settings),
  })
}
