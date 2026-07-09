package sync

type SyncRequest struct {
	ContractVersion string   `json:"contract_version"`
	RequestID       string   `json:"request_id"`
	IdempotencyKey  string   `json:"idempotency_key"`
	CallbackURL     string   `json:"callback_url"`
	Operation       string   `json:"operation"`
	Providers       []string `json:"providers"`
	Vehicle         Vehicle  `json:"vehicle"`
}

type Vehicle struct {
	ID           int64   `json:"id,omitempty"`
	ExternalCode string  `json:"external_code"`
	Brand        string  `json:"brand"`
	Model        string  `json:"model"`
	Version      string  `json:"version,omitempty"`
	Year         int     `json:"year"`
	ModelYear    int     `json:"model_year"`
	Price        float64 `json:"price"`
	Mileage      int     `json:"mileage,omitempty"`
	FuelType     string  `json:"fuel_type,omitempty"`
	Transmission string  `json:"transmission,omitempty"`
	Color        string  `json:"color,omitempty"`
	Description  string  `json:"description,omitempty"`
	Status       string  `json:"status"`
	UpdatedAt    string  `json:"updated_at,omitempty"`
}

type ProviderResult struct {
	Provider          string         `json:"provider"`
	Operation         string         `json:"operation"`
	Status            string         `json:"status"`
	ExternalReference *string        `json:"external_reference"`
	ErrorMessage      string         `json:"error_message,omitempty"`
	ResponsePayload   map[string]any `json:"response_payload"`
}

type SyncResponse struct {
	RequestID         string              `json:"request_id"`
	Status            string              `json:"status"`
	Message           string              `json:"message"`
	AcceptedProviders []string            `json:"accepted_providers"`
	Results           []ProviderResult    `json:"results,omitempty"`
	Errors            map[string][]string `json:"errors,omitempty"`
}

type CallbackPayload struct {
	VehicleExternalCode string         `json:"vehicle_external_code"`
	Provider            string         `json:"provider"`
	Operation           string         `json:"operation"`
	Status              string         `json:"status"`
	ExternalReference   *string        `json:"external_reference"`
	ErrorMessage        string         `json:"error_message,omitempty"`
	ResponsePayload     map[string]any `json:"response_payload,omitempty"`
}
