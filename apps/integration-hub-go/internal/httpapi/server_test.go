package httpapi

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"autosync-hub/integration-hub-go/internal/config"
	"autosync-hub/integration-hub-go/internal/provider"
	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

func TestSyncRequestsEndpointAcceptsValidPayload(t *testing.T) {
	server := testServer("")
	body := bytes.NewBufferString(`{
		"contract_version": "2026-07-09",
		"request_id": "request-1",
		"idempotency_key": "key-1",
		"callback_url": "http://127.0.0.1/callback",
		"operation": "publish",
		"providers": ["olx", "mercado_livre"],
		"vehicle": {
			"external_code": "CAR-001",
			"brand": "Honda",
			"model": "Civic",
			"version": "EXL 2.0",
			"year": 2020,
			"model_year": 2021,
			"price": 118900,
			"status": "active"
		}
	}`)

	request := httptest.NewRequest(http.MethodPost, "/sync-requests", body)
	recorder := httptest.NewRecorder()

	server.Handler().ServeHTTP(recorder, request)

	if recorder.Code != http.StatusAccepted {
		t.Fatalf("expected status 202, got %d: %s", recorder.Code, recorder.Body.String())
	}

	var response autosyncsync.SyncResponse
	if err := json.NewDecoder(recorder.Body).Decode(&response); err != nil {
		t.Fatalf("decode response: %v", err)
	}

	if response.Status != "accepted" {
		t.Fatalf("expected accepted response, got %s", response.Status)
	}
}

func TestSyncRequestsEndpointRejectsInvalidPayload(t *testing.T) {
	server := testServer("")
	request := httptest.NewRequest(http.MethodPost, "/sync-requests", bytes.NewBufferString(`{"providers":["bad"]}`))
	recorder := httptest.NewRecorder()

	server.Handler().ServeHTTP(recorder, request)

	if recorder.Code != http.StatusUnprocessableEntity {
		t.Fatalf("expected status 422, got %d", recorder.Code)
	}
}

func TestSyncRequestsEndpointRequiresTokenWhenConfigured(t *testing.T) {
	server := testServer("secret")
	request := httptest.NewRequest(http.MethodPost, "/sync-requests", bytes.NewBufferString(`{}`))
	recorder := httptest.NewRecorder()

	server.Handler().ServeHTTP(recorder, request)

	if recorder.Code != http.StatusUnauthorized {
		t.Fatalf("expected status 401, got %d", recorder.Code)
	}
}

func testServer(token string) *Server {
	cfg := config.Config{
		Addr:            ":8080",
		ContractVersion: "2026-07-09",
		ServiceToken:    token,
	}
	service := autosyncsync.NewService(provider.NewSimulator(), nil)

	return NewServer(cfg, service)
}
