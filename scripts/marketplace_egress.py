#!/usr/bin/python3
"""Разрешённые HTTPS-направления www-data через штатный маршрут NetBird."""

import fcntl
import pwd
import shlex
import socket
import subprocess


with open('/run/lock/wms-marketplace-egress.lock', 'w') as lock:
    try:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        raise SystemExit(0)

    # Сначала разрешаем все имена: сбой DNS не удаляет действующие исключения.
    addresses = set()
    for domain in ('api-seller.ozon.ru', 'content-api.wildberries.ru', 'api.partner.market.yandex.ru'):
        addresses.update(entry[4][0] for entry in socket.getaddrinfo(
            domain, 443, socket.AF_INET, socket.SOCK_STREAM))
    if not addresses:
        raise SystemExit('DNS не вернул IPv4-адреса API маркетплейсов')

    command = ['/usr/sbin/iptables', '-w', '-t', 'mangle']
    rules = [shlex.split(line) for line in subprocess.check_output(
        command + ['-S', 'OUTPUT'], text=True).splitlines() if line.startswith('-A ')]
    tag = 'wms-marketplace-https'
    own = [rule for rule in rules if '--comment' in rule and rule[rule.index('--comment') + 1] == tag]
    uid = str(pwd.getpwnam('www-data').pw_uid)
    desired = [
        ['-A', 'OUTPUT', '-d', address + '/32', '-p', 'tcp', '-m', 'owner',
         '--uid-owner', uid, '-m', 'tcp', '--dport', '443', '-m', 'comment',
         '--comment', tag, '-j', 'RETURN']
        for address in sorted(addresses)
    ]
    # Не переставляем правила из-за независимых исключений DaData.
    marking = next((i for i, rule in enumerate(rules) if '-j' in rule
                    and rule[rule.index('-j') + 1] == 'MARK'
                    and '--uid-owner' in rule and rule[rule.index('--uid-owner') + 1] == uid), len(rules))
    if own == desired and all(rules.index(rule) < marking for rule in own):
        raise SystemExit(0)

    # Удаляем по полному правилу, а не номеру: соседние правила могут меняться.
    for rule in own:
        subprocess.run(command + ['-D'] + rule[1:], check=True)
    for rule in reversed(desired):
        subprocess.run(command + ['-I', 'OUTPUT', '1'] + rule[2:], check=True)
