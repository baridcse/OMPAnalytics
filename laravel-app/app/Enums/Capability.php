<?php

namespace App\Enums;

enum Capability: string
{
    case Metrics = 'metrics';
    case Reviews = 'reviews';
    case AdRevenue = 'ad_revenue';
    case Analytics = 'analytics';
    case Leads = 'leads';
}
