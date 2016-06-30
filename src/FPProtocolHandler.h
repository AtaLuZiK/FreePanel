#ifndef FPPROTOCOLHANDLER_H_
#define FPPROTOCOLHANDLER_H_

#include "IOSession.h"
#include "IOHandler.h"

class FPProtocolHandler: public IOHandler
{
public:
    void OnDataReceived(IOSession& session, void *data, size_t length) override;

private:
    void OnTestCommand(IOSession& session, FPPacket *pPacket);
    void OnDisconnectCommand(IOSession& session, FPPacket *pPacket);
    void OnVHostCommand(IOSession& session, FPPacket *pPacket);
    void OnGetVHost(IOSession& session, const char *domain = nullptr);

};

#endif /* FPPROTOCOLHANDLER_H_ */
