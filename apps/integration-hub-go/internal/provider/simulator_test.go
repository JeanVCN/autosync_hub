package provider

import (
	"context"
	"testing"

	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

func TestSimulatorFailsIcarrosWhenVersionIsMissing(t *testing.T) {
	simulator := NewSimulator()

	result := simulator.Process(context.Background(), autosyncsync.SyncRequest{
		Operation: "publish",
		Vehicle: autosyncsync.Vehicle{
			ExternalCode: "CAR-001",
		},
	}, "icarros")

	if result.Status != "failed" {
		t.Fatalf("expected failed status, got %s", result.Status)
	}

	if result.ErrorMessage == "" {
		t.Fatal("expected an error message")
	}
}

func TestSimulatorReturnsProcessingForMercadoLivre(t *testing.T) {
	simulator := NewSimulator()

	result := simulator.Process(context.Background(), autosyncsync.SyncRequest{
		Operation: "publish",
		Vehicle: autosyncsync.Vehicle{
			ExternalCode: "CAR-001",
			Version:      "EXL 2.0",
		},
	}, "mercado_livre")

	if result.Status != "processing" {
		t.Fatalf("expected processing status, got %s", result.Status)
	}

	if result.ExternalReference == nil {
		t.Fatal("expected external reference")
	}
}
