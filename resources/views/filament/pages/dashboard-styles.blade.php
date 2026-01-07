@php
    $widgets = $this->getWidgets();
@endphp

<x-filament-panels::page>
    <style>
        /* STAT CARDS ONLY - 2 COLUMNS (widget lain tetap normal) */
        
        /* Target HANYA stat cards container */
        .fi-wi-stats-overview-stats-ctn {
            display: grid !important;
            gap: 1.5rem !important;
            overflow: visible !important;
        }
        
        /* Mobile: 1 column */
        .fi-wi-stats-overview-stats-ctn {
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
        }
        
        /* Tablet & Desktop: 2 columns (ATAS BAWAH) */
        @media (min-width: 768px) {
            .fi-wi-stats-overview-stats-ctn {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        
        /* Large: 2 columns */
        @media (min-width: 1024px) {
            .fi-wi-stats-overview-stats-ctn {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        
        /* XL: 2 columns */
        @media (min-width: 1280px) {
            .fi-wi-stats-overview-stats-ctn {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        
        /* XXL: 2 columns */
        @media (min-width: 1536px) {
            .fi-wi-stats-overview-stats-ctn {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        
        /* Card - AUTO HEIGHT to expand with content */
        .fi-wi-stats-overview-stat {
            padding: 2rem !important;
            overflow: visible !important;
            position: relative !important;
            min-height: 160px !important;
            height: auto !important;
            display: block !important;
        }
        
        /* Inner grid - allow overflow & proper spacing */
        .fi-wi-stats-overview-stat > .grid {
            overflow: visible !important;
            padding-bottom: 0 !important;
            min-height: auto !important;
        }
        
        /* LABEL - one line only */
        .fi-wi-stats-overview-stat-label {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
        }
        
        /* VALUE - one line only */
        .fi-wi-stats-overview-stat-value {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
        }
        
        /* DESCRIPTION - wrap if needed */
        .fi-wi-stats-overview-stat-description {
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            max-width: 100% !important;
            line-height: 1.5 !important;
        }
        
        /* Container for description + icon - allow wrapping */
        .fi-wi-stats-overview-stat .flex.items-center.gap-x-1 {
            overflow: visible !important;
            flex-wrap: wrap !important;
        }
        
        /* Icon - keep size */
        .fi-wi-stats-overview-stat-description-icon {
            flex-shrink: 0 !important;
        }
        
        /* Chart - relative position so card expands */
        .fi-wi-stats-overview-stat-chart {
            position: relative !important;
            margin-top: 1rem !important;
            height: 50px !important;
            overflow: hidden !important;
        }
        
        /* Content grid */
        .fi-wi-stats-overview-stat > .grid {
            position: relative !important;
            min-height: auto !important;
            height: auto !important;
        }
        
        /* Force all text elements to wrap */
        .fi-wi-stats-overview-stat span,
        .fi-wi-stats-overview-stat div:not(.fi-wi-stats-overview-stat-chart) {
            overflow: visible !important;
            text-overflow: clip !important;
        }
        
        /* Remove any max-width restrictions from parent */
        .fi-wi-stats-overview-stat .flex,
        .fi-wi-stats-overview-stat .grid {
            max-width: 100% !important;
        }
        
        /* Ensure no hidden overflow anywhere */
        .fi-wi-stats-overview-stat,
        .fi-wi-stats-overview-stat *:not(.fi-wi-stats-overview-stat-chart) {
            overflow: visible !important;
        }
        
        /* Force proper box sizing */
        .fi-wi-stats-overview-stat * {
            box-sizing: border-box !important;
        }
        
        /* Force card to expand with all content */
        .fi-wi-stats-overview-stat {
            box-sizing: border-box !important;
        }
        
        /* All children respect card boundaries */
        .fi-wi-stats-overview-stat * {
            box-sizing: border-box !important;
        }
        
        /* DEBUG - Orange border to confirm CSS loads */
        .fi-wi-stats-overview-stat {
            border: 2px solid rgba(255, 140, 0, 0.3) !important;
        }
    </style>

    <x-filament-widgets::widgets
        :widgets="$widgets"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
