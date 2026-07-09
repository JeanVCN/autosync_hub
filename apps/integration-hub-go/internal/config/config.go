package config

import (
	"os"
	"strconv"
	"time"
)

type Config struct {
	Addr                string
	ContractVersion     string
	ServiceToken        string
	CallbackTimeout     time.Duration
	ProviderMaxAttempts int
	ProviderBackoff     time.Duration
}

func FromEnv() Config {
	return Config{
		Addr:                envOrDefault("PORT", ":8080"),
		ContractVersion:     envOrDefault("INTEGRATION_CONTRACT_VERSION", "2026-07-09"),
		ServiceToken:        os.Getenv("INTEGRATION_HUB_TOKEN"),
		CallbackTimeout:     time.Duration(intEnvOrDefault("CALLBACK_TIMEOUT_SECONDS", 5)) * time.Second,
		ProviderMaxAttempts: intEnvOrDefault("PROVIDER_MAX_ATTEMPTS", 3),
		ProviderBackoff:     time.Duration(intEnvOrDefault("PROVIDER_BACKOFF_MS", 200)) * time.Millisecond,
	}
}

func envOrDefault(key string, fallback string) string {
	if value := os.Getenv(key); value != "" {
		if key == "PORT" && value[0] != ':' {
			return ":" + value
		}

		return value
	}

	return fallback
}

func intEnvOrDefault(key string, fallback int) int {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	parsed, err := strconv.Atoi(value)
	if err != nil || parsed <= 0 {
		return fallback
	}

	return parsed
}
